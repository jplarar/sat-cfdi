<?php

namespace Angle\CFDI\Node\CFDI40;

use Angle\CFDI\CFDINode;
use Angle\CFDI\CFDIException;
use Angle\CFDI\CFDIComplementInterface;

use Angle\CFDI\Node\Complement\CFDIFiscalRegistry;
use Angle\CFDI\Node\Complement\FiscalLegends\FiscalLegends;
use Angle\CFDI\Node\Complement\FiscalStamp;
use Angle\CFDI\Node\Complement\FoodVouchers\FoodVouchers;
use Angle\CFDI\Node\Complement\LocalTaxes\LocalTaxes;
use Angle\CFDI\Node\Complement\Payment\Payments as Payments10;
use Angle\CFDI\Node\Complement\Payment20\Payments as Payments20;
use Angle\CFDI\Node\Complement\PaymentsInterface;
use Angle\CFDI\Node\Complement\ThirdParties\ThirdParties;

use DateTime;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * @method static Complement createFromDOMNode(DOMNode $node)
 */
class Complement extends CFDINode implements CFDIComplementInterface
{
    #########################
    ##        PRESETS      ##
    #########################

    const NODE_NAME = "Complemento";
    public const NODE_NAME_EN = "complements";

    const NODE_NS = "cfdi";
    const NODE_NS_URI = "http://www.sat.gob.mx/cfd/4";
    const NODE_NS_NAME = self::NODE_NS . ":" . self::NODE_NAME;
    const NODE_NS_URI_NAME = self::NODE_NS_URI . ":" . self::NODE_NAME;

    protected static $baseAttributes = [];


    #########################
    ## PROPERTY NAME TRANSLATIONS ##
    #########################

    protected static $attributes = [
        // PropertyName => [spanish (official SAT), english]
    ];

    protected static $children = [
        'localTaxes' => [
            'keywords'  => ['ImpuestosLocales', 'localTaxes'],
            'class'     => LocalTaxes::class,
            'type'      => CFDINode::CHILD_ARRAY,
        ],
        'paymentComplement' => [
            'keywords' => ['Pagos', 'paymentComplement'],
            'class'     => Payments20::class,
            'type'      => CFDINode::CHILD_UNIQUE,
        ]
    ];


    #########################
    ##      PROPERTIES     ##
    #########################

    /**
     * @var CFDINode[]|array
     */
    protected $complements = [];

    /**
     * This is a special non-spec property used to keep track of the Complement nodes that were not parsed
     * this will be used to write warnings in other places
     * @var array
     */
    protected $unknownNodes = [];

    /** 
     * @var ?Payments20
     */
    protected ?Payments20 $paymentComplement = null;

    protected ?LocalTaxes $localTaxes = null;


    #########################
    ##     CONSTRUCTOR     ##
    #########################

    // constructor implemented in the CFDINode abstract class

    /**
     * @param DOMNode[]
     * @throws CFDIException
     */
    public function setChildrenFromDOMNodes(array $children): void
    {
        foreach ($children as $node) {
            if ($node instanceof DOMText) {
                // TODO: we are skipping the actual text inside the Node.. is this useful?
                // TODO: DOMText
                continue;
            }

            // Note: since we don't know the namespace of the possible Complements, we'll validate against its non-ns name
            $nodeNsUriName = $node->namespaceURI . ':' . $node->localName;
            switch ($nodeNsUriName) {

                case Payments20::NODE_NS_URI_NAME:
                    $payments = Payments20::createFromDOMNode($node);
                    $this->setPaymentComplement($payments);
                    break;
                case LocalTaxes::NODE_NS_URI_NAME:
                    $complement = LocalTaxes::createFromDOMNode($node);
                    $this->setLocalTaxes($complement);
                    break;
                /* case FiscalStamp::NODE_NS_URI_NAME:
                    $stamp = FiscalStamp::createFromDOMNode($node);
                    $this->addFiscalStamp($stamp);
                    break;
                case Payments10::NODE_NS_URI_NAME:
                    $payments = Payments10::createFromDOMNode($node);
                    $this->addComplement($payments);
                    break;
                case CFDIFiscalRegistry::NODE_NS_URI_NAME:
                    $complement = CFDIFiscalRegistry::createFromDOMNode($node);
                    $this->addComplement($complement);
                    break;
                case FiscalLegends::NODE_NS_URI_NAME:
                    $complement = FiscalLegends::createFromDOMNode($node);
                    $this->addComplement($complement);
                    break;
                case FoodVouchers::NODE_NS_URI_NAME:
                    $complement = FoodVouchers::createFromDOMNode($node);
                    $this->addComplement($complement);
                    break;
                 this complement is used as an ItemComplement..
                case ThirdParties::NODE_NS_URI_NAME:
                    $complement = ThirdParties::createFromDOMNode($node);
                    $this->addComplement($complement);
                    break;
                */

                // TODO: implement other types of nodes
                /*
                default:
                    throw new CFDIException(sprintf("Unknown children node '%s' in %s", $node->nodeName, self::NODE_NS_NAME));
                */

                default:
                    // this is an unknown node, we'll register it
                    $this->unknownNodes[] = $node->nodeName;
            }
        }
    }


    #########################
    ## CFDI NODE TO DOM TRANSLATION
    #########################

    public function toDOMElement(DOMDocument $dom): DOMElement
    {
        $node = $dom->createElementNS(self::NODE_NS_URI, self::NODE_NS_NAME);

        foreach ($this->getAttributes() as $attr => $value) {
            $node->setAttribute($attr, $value);
        }

        // Complements node (array)
        if($this->paymentComplement){
            $paymentComplementNode = $this->paymentComplement->toDOMElement($dom);
            $node->appendChild($paymentComplementNode);
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

    /**
     * @return array
     */
    public function getUnknownNodes(): array
    {
        return $this->unknownNodes;
    }




    #########################
    ## CHILDREN
    #########################

    /**
     * This method will return the first encountered FiscalStamp inside the Complements items
     * @return FiscalStamp
     */
    public function getFiscalStamp(): ?FiscalStamp
    {
        foreach ($this->complements as $c) {
            if ($c instanceof FiscalStamp) {
                return $c;
            }
        }

        return null;
    }

    /**
     * This method will return the first encountered Payments inside the Complements items
     * @return PaymentsInterface
     */
    public function getPayment(): ?PaymentsInterface
    {
        foreach ($this->complements as $c) {
            if ($c instanceof Payments10) {
                return $c;
            }
            if ($c instanceof Payments20) {
                return $c;
            }
        }

        return null;
    }

    /**
     * This method will return the first encountered Payments20 inside the Complements items
     * @return Payments20|null
     */
    public function getPayment20(): ?Payments20
    {
        if ($this->paymentComplement instanceof Payments20) {
            return $this->paymentComplement;
        }

        return null;
    }

    /**
     * This method will return the first encountered LocalTaxes inside the Complements items
     * @return LocalTaxes
     */
    public function getLocalTaxes(): ?LocalTaxes
    {
        if ($this->localTaxes instanceof LocalTaxes) {
            return $this->localTaxes;
        }

        return null;
    }

    /**
     * @param LocalTaxes $localTaxes
     * @throws CFDIException
     * @return Complement
     */
    public function addLocalTaxes(LocalTaxes $localTaxes): self
    {
        // Check if there is another fiscal stamp
        if ($this->getLocalTaxes() !== null) {
            throw new CFDIException('Cannot add more than one LocalTaxes to the CFDI\'s Complements');
        }

        $this->complements[] = $localTaxes;
        return $this;
    }


    /**
     * @param FiscalStamp $stamp
     * @throws CFDIException
     * @return Complement
     */
    public function addFiscalStamp(FiscalStamp $stamp): self
    {
        // Check if there is another fiscal stamp
        if ($this->getFiscalStamp() !== null) {
            throw new CFDIException('Cannot add more than one FiscalStamp to the CFDI\'s Complements');
        }
        
        $this->complements[] = $stamp;
        return $this;
    }


    /**
     * Add a generic Complement that implements CFDINode
     * @param CFDINode $node
     * @return Complement
     */
    public function addComplement(CFDINode $node): self
    {
        $this->complements[] = $node;
        return $this;
    }


    /**
     * Get Complements as CFDINode
     * @return CFDINode[]
     */
    public function getComplements(): array
    {
        return $this->complements;
    }

    /**
     * @return Payments20|array
     */
    public function getPaymentComplement()
    {
        return $this->paymentComplement;
    }

    /**
     * @param Payments20|array $paymentComplement
     */
    public function setPaymentComplement($paymentComplement): void
    {
        $this->paymentComplement = $paymentComplement;
    }

    public function setLocalTaxes(LocalTaxes $localTaxes)
    {
        $this->localTaxes = $localTaxes;
    }

}