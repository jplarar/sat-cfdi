<?php

namespace Angle\CFDI\Invoice\Node;

use Angle\CFDI\CFDI;
use Angle\CFDI\CFDIException;

use Angle\CFDI\Invoice\CFDINode;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * @method static ItemTaxesRetainedList createFromDOMNode(DOMNode $node)
 */
class ItemTaxesRetainedList extends CFDINode
{
    #########################
    ##        PRESETS      ##
    #########################

    const NODE_NAME = "Retenciones";
    const NS_NODE_NAME = "cfdi:Retenciones";

    protected static $baseAttributes = [];


    #########################
    ## PROPERTY NAME TRANSLATIONS ##
    #########################

    protected static $attributes = [];


    #########################
    ##      PROPERTIES     ##
    #########################


    // CHILDREN NODES
    /**
     * @var ItemTaxesRetained[]
     */
    protected $retentions = [];



    #########################
    ##     CONSTRUCTOR     ##
    #########################

    // constructor implemented in the CFDINode abstract class

    /**
     * @param DOMNode[]
     * @throws CFDIException
     */
    public function setChildren(array $children): void
    {
        foreach ($children as $node) {
            if ($node instanceof DOMText) {
                // TODO: we are skipping the actual text inside the Node.. is this useful?
                continue;
            }

            switch ($node->localName) {
                case ItemTaxesRetained::NODE_NAME:
                    $retention = ItemTaxesRetained::createFromDomNode($node);
                    $this->addRetention($retention);
                    break;
                default:
                    throw new CFDIException(sprintf("Unknown children node '%s' in %s", $node->localName, self::NODE_NAME));
            }
        }
    }


    #########################
    ## INVOICE TO DOM TRANSLATION
    #########################

    public function toDOMElement(DOMDocument $dom): DOMElement
    {
        $node = $dom->createElement(self::NS_NODE_NAME);

        foreach ($this->getAttributes() as $attr => $value) {
            $node->setAttribute($attr, $value);
        }

        // Retentions node (array)
        foreach ($this->retentions as $retention) {
            $retentionNode = $retention->toDOMElement($dom);
            $node->appendChild($retentionNode);
        }

        return $node;
    }


    #########################
    ## VALIDATION
    #########################

    public function validate(): bool
    {
        // TODO: implement the full set of validation, including type and Business Logic

        return true;
    }


    #########################
    ## GETTERS AND SETTERS ##
    #########################

    // none


    #########################
    ## CHILDREN
    #########################

    /**
     * @return ItemTaxesRetained[]
     */
    public function getRetentions(): ?array
    {
        return $this->retentions;
    }

    /**
     * @param ItemTaxesRetained $retention
     * @return ItemTaxesRetainedList
     */
    public function addRetention(ItemTaxesRetained $retention): self
    {
        $this->retentions[] = $retention;
        return $this;
    }

    /**
     * @param ItemTaxesRetained[] $retentions
     * @return ItemTaxesRetainedList
     */
    public function setItems(array $retentions): self
    {
        $this->retentions = $retentions;
        return $this;
    }
}