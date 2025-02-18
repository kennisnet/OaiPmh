<?php

/*
 * This file is part of Kennisnet\OaiPmh.
 *
 * Kennisnet\OaiPmh is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Kennisnet\OaiPmh is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kennisnet\OaiPmh.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Kennisnet\OaiPmh;

use DOMElement;
use Kennisnet\OaiPmh\Exception\BadArgumentException;
use Kennisnet\OaiPmh\Exception\BadVerbException;
use Kennisnet\OaiPmh\Exception\CannotDisseminateFormatException;
use Kennisnet\OaiPmh\Exception\MultipleExceptions;
use Kennisnet\OaiPmh\Exception\NoMetadataFormatsException;
use Kennisnet\OaiPmh\Exception\NoRecordsMatchException;
use Kennisnet\OaiPmh\Exception\NoSetHierarchyException;
use Kennisnet\OaiPmh\Interfaces\Record\Header;
use Kennisnet\OaiPmh\Interfaces\Repository;
use Kennisnet\OaiPmh\Interfaces\Repository\Identity;
use Kennisnet\OaiPmh\Interfaces\ResultList as ResultListInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @example
 * <code>
 *
 * //create provider object
 * $provider = new Kennisnet\OaiPmh\Provider($someRepository);
 * //where some $someRepository is an implementation of \Kennisnet\OaiPmh\Interfaces\Repository
 *
 * // add request variables, this could be just $_GET or $_POST in case of a post but can also come from a different
 * // source
 * $provider->setRequest($get);
 *
 * //run the oai provider this will return a object containing all headers and output
 * $response = $provider->getResponse();
 *
 * //output headers, body and then exit (it is possible to do manipulations before outputting but this is not advised.
 * $response->outputAndExit();
 * </code>
 */
class Provider
{

    /**
     * @var array{
     *     Identify:string[],
     *     ListMetadataFormats: string[],
     *     ListSets: string[],
     *     GetRecord: string[],
     *     ListIdentifiers: string[],
     *     ListRecords: string[]
     *}
     */
    private static $verbs = [
        "Identify" => [],
        "ListMetadataFormats" => ['identifier'],
        "ListSets" => ['resumptionToken'],
        "GetRecord" => ['identifier', 'metadataPrefix'],
        "ListIdentifiers" => ['from', 'until', 'metadataPrefix', 'set', 'resumptionToken'],
        "ListRecords" => ['from', 'until', 'metadataPrefix', 'set', 'resumptionToken'],
    ];

    protected LoggerInterface $logger;

    private string $verb;

    private ResponseDocument $response;

    private Repository $repository;

    /**
     * @var array<mixed,mixed>
     */
    private array $params = [];

    private ?ServerRequestInterface $request;

    /**
     * @var array<int, XmlSaveString|\DOMDocument>
     */
    private array $records = [];

    public function __construct(
        Repository $repository,
        ?ServerRequestInterface $request = null,
        ?LoggerInterface $logger = null
    )
    {
        if ($logger === null) {
            $this->logger = new NullLogger();
        } else {
            $this->logger = $logger;
        }

        $this->repository = $repository;

        if ($request) {
            $this->setRequest($request);
        }
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        if ($request->getMethod() === 'POST') {
            $this->params = (array)$request->getParsedBody();
        } else {
            $this->params = $request->getQueryParams();
        }
        $this->request = $request;
    }

    private function toUtcDateTime(\DateTimeInterface $time): string
    {
        $UTC = new \DateTimeZone("UTC");
        if (method_exists($time, 'setTimezone')) {
            $time->setTimezone($UTC);
        }

        return $time->format('Y-m-d\TH:i:s\Z');
    }

    public function getResponse(): ResponseInterface
    {
        $this->response = new ResponseDocument();
        $this->response->addElement("responseDate", $this->toUtcDateTime(new \DateTime()));
        $requestNode = $this->response->createElement("request", $this->repository->getBaseUrl());
        if ($this->response->getDocument()->documentElement) {
            $this->response->getDocument()->documentElement->appendChild($requestNode);
        }
        try {
            $this->checkVerb();
            $verbOutput = $this->doVerb();

            // we are sure now that all request variables are correct otherwise an error would have been thrown
            foreach ($this->params as $k => $v) {
                $requestNode->setAttribute($k, $v);
            }

            // the element is only added when everything went fine, otherwise we would add error node(s) in the catch
            // block below
            if ($this->response->getDocument()->documentElement) {
                $this->response->getDocument()->documentElement->appendChild($verbOutput);
            }
            // Shift the records from the records stack and add them to the DOM tree
            // Records proper are always stored in the 'metadata' node
            /** @var \DOMNode $item */
            foreach ($this->response->getDocument()->getElementsByTagName('metadata') as $item) {
                $record = array_shift($this->records);
                if (isset($record)) {
                    if ($record instanceof \DOMDocument) {
                        /*                        $content = trim(str_replace('<?xml version="1.0"?>', '', $record->saveXML()),"\n\t\0 ");*/
                        $node = $this->response->getDocument()->importNode($record->documentElement, true);
                        $item->appendChild($node);
                    } else {
                        $item->nodeValue = (string)$record;
                    }
                }
            }
        } catch (MultipleExceptions $errors) {
            //multiple errors happened add all of the to the response
            foreach ($errors as $error) {
                $this->logException($error);
                $this->response->addError($error);
            }
        } catch (\Exception $error) {
            //add this error to the response
            if ($error instanceof Exception) {
                $this->response->addError($error);
                $this->logException($error);
            } else {
                $exception = new Exception($error->getMessage());
                $this->logException($exception);
                $this->response->addError(new Exception($error->getMessage()));
            }
        }

        return $this->response->getResponse();
    }

    /**
     * executes the right function for the current verb
     * @throws BadVerbException
     */
    private function doVerb(): DOMElement
    {
        switch ($this->verb) {
            case "Identify":
                return $this->identify();
            case "ListMetadataFormats":
                return $this->listMetadataFormats();
            case "ListSets":
                return $this->listSets();
            case "ListRecords":
                return $this->listRecords();
            case "ListIdentifiers":
                return $this->listIdentifiers();
            case "GetRecord":
                return $this->getRecord();
            default:
                //shouldn't be possible to come here because verb was already checked, but just in case
                throw new BadVerbException("$this->verb is not a valid verb");
        }
    }

    /**
     * handles GetRecord requests
     * @throws BadArgumentException
     */
    private function getRecord(): DOMElement
    {
        $checks = [
            function () {
                if (!isset($this->params['identifier'])) {
                    throw new BadArgumentException("Missing required argument identifier");
                }
            },
            function () {
                if (!isset($this->params['metadataPrefix'])) {
                    throw new BadArgumentException("Missing required argument metadataPrefix");
                }
                $this->checkMetadataPrefix(
                    $this->params['metadataPrefix'],
                    isset($this->params['identifier']) ? $this->params['identifier'] : null
                );
            },
        ];
        $this->doChecks($checks);

        $record = $this->repository->getRecord($this->params['metadataPrefix'], $this->params['identifier']);
        $recordNode = $this->response->createElement('record');

        $header = $record->getHeader();
        $recordNode->appendChild($this->getRecordHeaderNode($header));

        // Only add metadata and about if the record is not deleted.
        if (!$header->isDeleted() && $record->getMetadata() !== null) {
            $recordNode->appendChild($this->response->createElement('metadata'));

            // Push the record itself on the records stack
            array_push($this->records, $record->getMetadata());

            //only add an 'about' node if it's not null
            $about = $record->getAbout();
            if ($about !== null) {
                $recordNode->appendChild($this->response->createElement('about', $about));
            }
        }

        $getRecordNode = $this->response->createElement('GetRecord');
        $getRecordNode->appendChild($recordNode);

        return $getRecordNode;
    }

    private function identify(): DOMElement
    {
        $identity = $this->repository->identify();
        $identityNode = $this->response->createElement('Identify');

        // create a node for each property of identify
        $identityNode->appendChild($this->response->createElement('repositoryName', $identity->getRepositoryName()));
        $identityNode->appendChild($this->response->createElement('baseURL', $this->repository->getBaseUrl()));
        $identityNode->appendChild($this->response->createElement('protocolVersion', '2.0'));
        foreach ($identity->getAdminEmails() ?? [] as $email) {
            $identityNode->appendChild($this->response->createElement('adminEmail', $email));
        }
        $identityNode->appendChild(
            $this->response->createElement('earliestDatestamp', $this->toUtcDateTime($identity->getEarliestDatestamp()))
        );
        $identityNode->appendChild($this->response->createElement('deletedRecord', $identity->getDeletedRecord()));
        $identityNode->appendChild($this->response->createElement('granularity', $identity->getGranularity()));
        if ($identity->getCompression()) {
            $identityNode->appendChild($this->response->createElement('compression', $identity->getCompression()));
        }
        if ($identity->getDescription() !== null) {
            $identityNode->appendChild($this->response->createElement('description', $identity->getDescription()));
        }

        return $identityNode;
    }

    /**
     * handles ListMetadataFormats requests
     * @throws NoMetadataFormatsException
     */
    private function listMetadataFormats(): DOMElement
    {
        $listNode = $this->response->createElement('ListMetadataFormats');

        $identifier = $this->params['identifier'] ?? null;
        $formats = $this->repository->listMetadataFormats($identifier);

        if (!count($formats)) {
            throw new NoMetadataFormatsException("There are no metadata formats available for the specified item.");
        }

        //create a node for each metadataFormat
        foreach ($formats as $format) {
            $formatNode = $this->response->createElement('metadataFormat');
            $formatNode->appendChild($this->response->createElement("metadataPrefix", $format->getPrefix()));
            $formatNode->appendChild($this->response->createElement("schema", $format->getSchema()));
            $formatNode->appendChild($this->response->createElement("metadataNamespace", $format->getNamespace()));
            $listNode->appendChild($formatNode);
        }

        return $listNode;
    }

    /**
     * checks if the provided verb is correct and if the arguments supplied are allowed for this verb
     * @throws BadArgumentException
     * @throws BadVerbException
     * @throws MultipleExceptions
     */
    private function checkVerb(): void
    {
        if (!isset($this->params['verb'])) {
            throw new BadVerbException("Verb is missing");
        }

        $this->verb = $this->params['verb'];
        if (is_array($this->verb)) {
            throw new BadVerbException("Only 1 verb allowed, multiple given");
        }
        if (!array_key_exists($this->verb, self::$verbs)) {
            throw new BadVerbException("$this->verb is not a valid verb");
        }

        $requestParams = $this->params;
        unset($requestParams['verb']);

        $errors = [];
        foreach (array_diff_key($requestParams, array_flip(self::$verbs[$this->verb])) as $key => $value) {
            $errors[] = new BadArgumentException(
                "Argument {$key} is not allowed for verb $this->verb. " .
                "Allowed arguments are: " . implode(", ", self::$verbs[$this->verb])
            );
        }
        if (count($errors)) {
            throw (new MultipleExceptions())->setExceptions($errors);
        }

        //if the resumption token is set it should be the only argument
        if (isset($requestParams['resumptionToken']) && count($requestParams) > 1) {
            throw new BadArgumentException("resumptionToken can not be used together with other arguments");
        }
    }

    /**
     * handles ListSets requests
     * @throws NoSetHierarchyException
     */
    private function listSets(): DOMElement
    {
        $listNode = $this->response->createElement('ListSets');

        // fetch the sets either by resumption token or without
        if (isset($this->params['resumptionToken'])) {
            $sets = $this->repository->listSetsByToken($this->params['resumptionToken']);
        } else {
            $sets = $this->repository->listSets();
            if (!count($sets->getItems())) {
                throw new NoSetHierarchyException("The repository does not support sets.");
            }
        }

        //create node for all sets
        foreach ($sets->getItems() as $set) {
            $setNode = $this->response->createElement('set');
            $setNode->appendChild($this->response->createElement('setSpec', $set->getSpec()));
            $setNode->appendChild($this->response->createElement('setName', $set->getName()));
            if ($set->getDescription() !== null) {
                $setNode->appendChild($this->response->createElement('setDescription', $set->getDescription()));
            }
            $listNode->appendChild($setNode);
        }

        $this->addResumptionToken($sets, $listNode);

        return $listNode;
    }

    /**
     * handles ListSets Records
     * @throws NoSetHierarchyException
     * @throws NoRecordsMatchException
     */
    private function listRecords(): DOMElement
    {
        $listNode = $this->response->createElement('ListRecords');
        if (isset($this->params['resumptionToken'])) {
            $records = $this->repository->listRecordsByToken($this->params['resumptionToken']);
        } else {
            [$metadataPrefix, $from, $until, $set] = $this->getRecordListParams();
            $records = $this->repository->listRecords(100, 0, $metadataPrefix, $from, $until, $set);

            if (!count($records->getItems())) {
                //maybe this is because someone tries to fetch from a set and we don't support that
                if ($set && !count($this->repository->listSets()->getItems())) {
                    throw new NoSetHierarchyException("The repository does not support sets.");
                }
                throw new NoRecordsMatchException(
                    "The combination of the values of the from, until, set and metadataPrefix arguments "
                    . "results in an empty list."
                );
            }
        }

        //create 'record' node for each record with a 'header', 'metadata' and possibly 'about' node
        foreach ($records->getItems() as $record) {
            $recordNode = $this->response->createElement('record');

            $header = $record->getHeader();
            $recordNode->appendChild($this->getRecordHeaderNode($header));

            // Only add metadata and about if the record is not deleted.
            if (!$header->isDeleted()) {

                $recordNode->appendChild(

                    $this->response->createElement('metadata')
                );

                // Push the record itself on the records stack and will be added to the response body in the self::getResponse()
                array_push($this->records, $record->getMetadata());

                //only add an 'about' node if it's not null
                $about = $record->getAbout();
                if ($about !== null) {
                    $recordNode->appendChild($this->response->createElement('about', (string)$about));
                }
            }
            $listNode->appendChild($recordNode);
        }

        $this->addResumptionToken($records, $listNode);

        return $listNode;
    }

    /**
     * handles ListIdentifiers requests
     * @throws NoSetHierarchyException
     * @throws NoRecordsMatchException
     */
    private function listIdentifiers(): DOMElement
    {
        $listNode = $this->response->createElement('ListIdentifiers');
        if (isset($this->params['resumptionToken'])) {
            $records = $this->repository->listRecordsByToken($this->params['resumptionToken']);
        } else {
            [$metadataPrefix, $from, $until, $set] = $this->getRecordListParams();
            $records = $this->repository->listRecords(100, 0, $metadataPrefix, $from, $until, $set);

            if (!count($records->getItems())) {
                //maybe this is because someone tries to fetch from a set and we don't support that
                if ($set && !count($this->repository->listSets()->getItems())) {
                    throw new NoSetHierarchyException("The repository does not support sets.");
                }
                throw new NoRecordsMatchException(
                    "The combination of the values of the from, until, set and metadataPrefix arguments "
                    . "results in an empty list."
                );
            }
        }

        // create 'record' with only headers
        foreach ($records->getItems() as $record) {
            $listNode->appendChild($this->getRecordHeaderNode($record->getHeader()));
        }

        $this->addResumptionToken($records, $listNode);

        return $listNode;
    }

    /**
     * Converts the header of a record to a header node, used for both ListRecords and ListIdentifiers
     */
    private function getRecordHeaderNode(Header $header): DOMElement
    {
        $headerNode = $this->response->createElement('header');
        $headerNode->appendChild($this->response->createElement('identifier', $header->getIdentifier()));
        $headerNode->appendChild(
            $this->response->createElement('datestamp', $this->toUtcDateTime($header->getDatestamp()))
        );
        foreach ($header->getSetSpecs() as $setSpec) {
            $headerNode->appendChild($this->response->createElement('setSpec', $setSpec));
        }
        if ($header->isDeleted()) {
            $headerNode->setAttribute("status", "deleted");
        }

        return $headerNode;
    }

    /**
     * does all the checks in the closures and merge any exceptions into one big exception
     *
     * @param \Closure[] $checks
     *
     * @throws MultipleExceptions
     */
    private function doChecks(array $checks): void
    {
        $errors = [];
        foreach ($checks as $check) {
            try {
                $check();
            } catch (Exception $e) {
                $errors[] = $e;
            }
        }
        if (count($errors)) {
            throw (new MultipleExceptions)->setExceptions($errors);
        }
    }

    /**
     * Converts a date coming from a request param and converts it to a \DateTime
     *
     *
     * @return array<int, mixed>
     * @throws BadArgumentException when the date is invalid or not supplied in the right format
     */
    private function parseRequestDate(string $date): array
    {
        $timezone = new \DateTimeZone("UTC");

        if (preg_match('#^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$#', $date)) {
            $parsedDate = date_create_from_format('Y-m-d\TH:i:s\Z', $date, $timezone);
            $granularity = Identity::GRANULARITY_YYYY_MM_DDTHH_MM_SSZ;
        } elseif (preg_match('#^\d{4}-\d{2}-\d{2}$#', $date)) {
            // Add ! to format to set time to 00:00:00
            $parsedDate = date_create_from_format('!Y-m-d', $date, $timezone);
            $granularity = Identity::GRANULARITY_YYYY_MM_DD;
        } else {
            throw new BadArgumentException("Expected a data in one of the following formats: " .
                Identity::GRANULARITY_YYYY_MM_DDTHH_MM_SSZ . " OR " .
                Identity::GRANULARITY_YYYY_MM_DD . " FOUND " . $date);
        }

        /** @var array{error_count: int, warning_count: int} $parseResult */
        $parseResult = date_get_last_errors();
        if (!$parsedDate || (($parseResult['error_count'] ?? 0) > 0) || (($parseResult['warning_count'] ?? 0) > 0)) {
            throw new BadArgumentException("$date is not a valid date");
        }

        return [$parsedDate, $granularity];
    }

    /**
     * Adds a resumptionToken to a a listNode if the is a resumption token otherwise it does nothing
     */
    private function addResumptionToken(ResultListInterface $resultList, DomElement $listNode): void
    {
        // @TODO Add support for expirationDate

        $resumptionTokenNode = null;

        if ($resultList->getResumptionToken()) {
            $resumptionTokenNode = $this->response->createElement('resumptionToken', $resultList->getResumptionToken());
        } elseif ($resultList->getCompleteListSize() !== null || $resultList->getCursor() !== null) {
            // An empty resumption token with attributes completeListSize and/or cursor.
            $resumptionTokenNode = $this->response->createElement('resumptionToken');
        }

        if ($resumptionTokenNode !== null) {
            if ($resultList->getCompleteListSize() !== null) {
                $resumptionTokenNode->setAttribute('completeListSize', (string)$resultList->getCompleteListSize());
            }

            if ($resultList->getCursor() !== null) {
                $resumptionTokenNode->setAttribute('cursor', (string)$resultList->getCursor());
            }

            $listNode->appendChild($resumptionTokenNode);
        }
    }

    /**
     * Parses request arguments used by both ListIdentifiers and ListRecords
     * @return array<int,mixed>
     * @throws BadArgumentException
     */
    private function getRecordListParams(): array
    {
        $metadataPrefix = null;
        $from = null;
        $until = null;
        $fromGranularity = null;
        $untilGranularity = null;
        $set = isset($this->params['set']) ? $this->params['set'] : null;

        $checks = [
            function () use (&$from, &$fromGranularity) {
                if (isset($this->params['from'])) {
                    [$from, $fromGranularity] = $this->parseRequestDate($this->params['from']);
                }
            },
            function () use (&$until, &$untilGranularity) {
                if (isset($this->params['until'])) {
                    [$until, $untilGranularity] = $this->parseRequestDate($this->params['until']);
                }
            },
            function () use (&$from, &$until) {
                if ($from !== null and $until !== null && $from > $until) {
                    throw new BadArgumentException(
                        'The `from` argument must be less than or equal to the `until` argument'
                    );
                }
            },
            function () use (&$from, &$until, &$fromGranularity, &$untilGranularity) {
                if ($from !== null and $until !== null && $fromGranularity !== $untilGranularity) {
                    throw new BadArgumentException('The `from` and `until` arguments have different granularity');
                }
            },
            function () use (&$fromGranularity) {
                if ($fromGranularity !== null &&
                    $fromGranularity === Identity::GRANULARITY_YYYY_MM_DDTHH_MM_SSZ &&
                    $this->repository->getGranularity() === Identity::GRANULARITY_YYYY_MM_DD) {
                    throw new BadArgumentException(
                        'The granularity of the `from` argument is not supported by this repository'
                    );
                }
            },
            function () use (&$untilGranularity) {
                if ($untilGranularity !== null &&
                    $untilGranularity === Identity::GRANULARITY_YYYY_MM_DDTHH_MM_SSZ &&
                    $this->repository->getGranularity() === Identity::GRANULARITY_YYYY_MM_DD) {
                    throw new BadArgumentException(
                        'The granularity of the `until` argument is not supported by this repository'
                    );
                }
            },
            function () use (&$metadataPrefix) {
                if (!isset($this->params['metadataPrefix'])) {
                    throw new BadArgumentException("Missing required argument metadataPrefix");
                }
                $metadataPrefix = $this->params['metadataPrefix'];
                if (is_array($metadataPrefix)) {
                    throw new BadArgumentException("Only one metadataPrefix allowed");
                }
                $this->checkMetadataPrefix($metadataPrefix);
            },
        ];

        $this->doChecks($checks);

        return [$metadataPrefix, $from, $until, $set];
    }

    /**
     * Checks if the metadata prefix is in the available metadata formats list.
     * @throws CannotDisseminateFormatException
     */
    private function checkMetadataPrefix(string $metadataPrefix, ?string $identifier = null): void
    {
        $availableMetadataFormats = $this->repository->listMetadataFormats($identifier);

        $found = false;
        if (!empty($availableMetadataFormats)) {
            foreach ($availableMetadataFormats as $metadataFormat) {
                if ($metadataPrefix == $metadataFormat->getPrefix()) {
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            throw new CannotDisseminateFormatException(
                'The metadata format identified by the value given for the metadataPrefix argument '
                . 'is not supported by the item or by the repository.'
            );
        }
    }

    private function logException(\Exception $exception)
    {
        if (isset($this->request)) {
            $this->logger->critical($exception->getMessage(), ['request' => $this->request ? $this->request->getAttributes() : '']);
        } else {
            $this->logger->critical($exception->getMessage(), ['request' => null]);
        }
    }
}
