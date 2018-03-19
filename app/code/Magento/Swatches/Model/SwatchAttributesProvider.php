<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Swatches\Model;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute;
use Magento\Swatches\Helper\Data as SwatchesHelper;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\ObjectManager;

/**
 * Provide list of swatch attributes for product.
 */
class SwatchAttributesProvider
{
    /**
     * @var \Magento\Swatches\Helper\Data
     */
    protected $swatchesHelper;
    /**
     * @var Configurable
     */
    private $typeConfigurable;

    /**
     * @var SwatchAttributeCodes
     */
    private $swatchAttributeCodes;

    /**
     * Key is productId, value is list of attributes
     * @var Attribute[]
     */
    private $attributesPerProduct;

    /**
     * @var SwatchAttributeType
     */
    private $swatchTypeChecker;

    /**
     * SwatchAttributesProvider constructor.
     *
     * @param Configurable         $typeConfigurable
     * @param SwatchAttributeCodes $swatchAttributeCodes
     * @param SwatchesHelper|null    $swatchHelper
     * @param SwatchAttributeType|null $swatchTypeChecker
     */
    public function __construct(
        Configurable $typeConfigurable,
        SwatchAttributeCodes $swatchAttributeCodes,
        SwatchesHelper $swatchHelper = null,
        SwatchAttributeType $swatchTypeChecker = null
    ) {
        $this->typeConfigurable = $typeConfigurable;
        $this->swatchAttributeCodes = $swatchAttributeCodes;
        $this->swatchesHelper = $swatchHelper ?: ObjectManager::getInstance()->create(SwatchesHelper::class);
        $this->swatchTypeChecker = $swatchTypeChecker
            ?: ObjectManager::getInstance()->create(SwatchAttributeType::class);
    }

    /**
     * Provide list of swatch attributes for product. If product is not configurable return empty array
     * Key is productId, value is list of attributes
     *
     * @param Product $product
     * @return Attribute[]
     */
    public function provide(Product $product)
    {
        if ($product->getTypeId() !== Configurable::TYPE_CODE) {
            return [];
        }
        if (!isset($this->attributesPerProduct[$product->getId()])) {
            $configurableAttributes = $this->typeConfigurable->getConfigurableAttributes($product);
            $swatchAttributeCodeMap = $this->swatchAttributeCodes->getCodes();

            $swatchAttributes = [];
            foreach ($configurableAttributes as $configurableAttribute) {
                if (array_key_exists($configurableAttribute->getAttributeId(), $swatchAttributeCodeMap)) {
                    /** @var AbstractAttribute $productAttribute */
                    $productAttribute = $configurableAttribute->getProductAttribute();
                    if ($productAttribute !== null
                        && $this->swatchTypeChecker->isSwatchAttribute($productAttribute)
                    ) {
                        $swatchAttributes[$configurableAttribute->getAttributeId()] = $productAttribute;
                    }
                }
            }
            $this->attributesPerProduct[$product->getId()] = $swatchAttributes;
        }
        return $this->attributesPerProduct[$product->getId()];
    }

    /**
     * This method introduced only for the case when customer already has converted attribute.
     *
     * @param $productAttribute
     * @return bool
     * @deprecated
     */
    private function hasSwatchAttribute($productAttribute)
    {
        return $this->swatchesHelper->isSwatchAttribute($productAttribute);
    }
}
