<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * This class implements common methods to manipulate the immutable
 * structure of a SimpleXMLElement object from a SOAP XML message.
 * 
 * @since   1.17.1 (J) - 1.7.1 (WP)
 * 
 * @see     SimpleXMLElement
 * @see     DomDocument
 */
class VBOXmlSoap extends SimpleXMLElement
{
    /**
     * Appends the provided node.
     *
     * @param   SimpleXMLElement  $node  The node to search.
     * 
     * @return  mixed  The added child on success, false otherwise.
     */
    public function append(SimpleXMLElement $node)
    {
        // create a DOM starting from the current XML
        $dom = dom_import_simplexml($this);

        // import the VCM node onto the update node
        $appendNode = $dom->ownerDocument->importNode(dom_import_simplexml($node), $deep = true);

        if ($appendNode) {
            // append the new node into the specified position
            return $dom->appendChild($appendNode);    
        }
        
        return false;
    }

    /**
     * Finds the provided node within the document and replaces it
     * with the latter.
     *
     * @param   SimpleXMLElement  $node  The node to search.
     * 
     * @return  mixed  The replaced child on success, false otherwise.
     */
    public function replace(SimpleXMLElement $node)
    {
        // create a DOM starting from the current XML
        $dom = dom_import_simplexml($this);

        // returns a copy of the node to import and associates it with the current document
        $importNode = $dom->ownerDocument->importNode(dom_import_simplexml($node), $deep = true);

        if ($importNode) {
            // replace the existing node with the provided one
            $return = $dom->parentNode->replaceChild($importNode, $dom);
        }

        return false;
    }

    /**
     * Formats the XML string.
     * 
     * @return  string
     */
    public function formatXml()
    {
        $xmlFeed = $this->asXml();

        if (!$xmlFeed) {
            // prevent errors with empty XML strings
            return '';
        }

        // format XML string feed
        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($xmlFeed);
        $dom->formatOutput = true;

        return $dom->saveXML();
    }

    /**
     * Returns the SimpleXMLElement object of the main Soap body message.
     * 
     * @param   string  $ns         The XML namespace.
     * @param   bool    $isPrefix   Whether the namespace should be regarded as a prefix.
     * @param   string  $node       The default body node name.
     * 
     * @return  SimpleXMLElement
     */
    public function getSoapBody($ns = 'soap', $isPrefix = true, $node = 'Body')
    {
        return $this->children($ns, $isPrefix)->{$node}->children();
    }

    /**
     * Returns the SimpleXMLElement object from a recursive list of nested namespaces.
     * Useful to access a node that belongs to multiple and different namespaces.
     * 
     * @param   array   $recursiveNs    List of recursive namespace instruction pairs (ns, isPrefix, node).
     * 
     * @return  SimpleXMLElement
     * 
     * @since   1.17.2 (J) - 1.7.2 (WP)
     */
    public function getSoapRecursiveNsElement(array $recursiveNs)
    {
        // starting element
        $element = $this;

        // scan the list of namespace instructions to advance the elements
        foreach ($recursiveNs as $ind => $pairs) {
            // instructions
            list($ns, $isPrefix, $node) = $pairs;

            $node = (string) ($node ?? '');

            if (!$node) {
                // abort
                return $this;
            }

            // attempt to get the requested element
            $childElement = $element->children((string) ($ns ?? ''), (bool) ($isPrefix ?? true));

            if (!isset($childElement)) {
                // abort
                return $this;
            }

            if (count($recursiveNs) - 1 === $ind) {
                // exit from namespace
                $childElement = $childElement->children();
            } else {
                // enter the next node
                $childElement = $childElement->{$node};
            }

            // advance the element
            $element = $childElement;
        }

        return $element;
    }
}
