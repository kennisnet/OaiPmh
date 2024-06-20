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

use DOMDocument;
use DOMElement;
use GuzzleHttp\Psr7\Response;
use Kennisnet\OaiPmh\Exception\NoRecordsMatchException;
use Psr\Http\Message\ResponseInterface;

class ResponseDocument
{
    private string $output;

    /**
     * @var string[]
     */
    private array $headers = ['Content-Type' => 'text/xml; charset=utf8'];

    private string $status = '200';

    private DOMDocument $document;

    public function getDocument(): DOMDocument
    {
        return $this->document;
    }

    public function setDocument(DOMDocument $document): void
    {
        $this->document = $document;
    }

    public function __construct()
    {
        $this->document               = new DOMDocument('1.0', 'UTF-8');
        $this->document->formatOutput = true;
        $documentElement              = $this->document->createElementNS('http://www.openarchives.org/OAI/2.0/',
            "OAI-PMH");
        $documentElement->setAttribute('xmlns', 'http://www.openarchives.org/OAI/2.0/');
        $documentElement->setAttributeNS(
            "http://www.w3.org/2001/XMLSchema-instance",
            'xsi:schemaLocation',
            'http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd'
        );

        $this->document->appendChild($documentElement);
    }

    public function addElement(string $name, ?string $value = null): DOMElement
    {
        $element = $this->createElement($name, $value);
        if($this->document->documentElement) {
            $this->document->documentElement->appendChild($element);
        }

        return $element;
    }

    public function addError(Exception $error): void
    {
        $errorNode = $this->addElement("error", $error->getMessage());

        if (!$error instanceof NoRecordsMatchException) {
            $this->status = '400';
        }

        if ($error->getErrorName()) {
            $errorNode->setAttribute("code", $error->getErrorName());
        } else {
            $errorNode->setAttribute("code", "badArgument");
        }
    }

    /**
     * @param string[] $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    public function addHeader(string $header): self
    {
        $this->headers [] = $header;

        return $this;
    }

    public function createElement(string $name, ?string $value = null): DOMElement
    {
        $nameSpace = 'http://www.openarchives.org/OAI/2.0/';

        return $this->document->createElementNS($nameSpace, $name, htmlspecialchars($value??'', ENT_XML1));
    }

    public function getResponse(): ResponseInterface
    {
        return new Response((int)$this->status, $this->headers, $this->document->saveXML() ?: '');
    }
}
