<?php

namespace MageOS\AutomaticTranslation\Setup\Patch\Data;

use Magento\Catalog\Model\Category;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use MageOS\AutomaticTranslation\Api\AttributeProviderInterface as AttributeProvider;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validator\ValidateException;

/**
 * Class AddLastTranslationDateCategoryAttribute
 * @package MageOS\AutomaticTranslation\Setup\Patch\Data
 */
class AddLastTranslationDateCategoryAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    protected EavSetupFactory $eavSetupFactory;

    /**
     * AddLastTranslationDateCategoryAttribute constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @return void
     * @throws LocalizedException
     * @throws ValidateException
     */
    public function apply(): void
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->removeAttribute(Category::ENTITY, AttributeProvider::LAST_TRANSLATION);
        $eavSetup->addAttribute(
            Category::ENTITY,
            AttributeProvider::LAST_TRANSLATION,
            [
                'visible_on_front' => false,
                'visible' => true,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'label' => AttributeProvider::LAST_TRANSLATION_LABEL,
                'source' => null,
                'type' => 'datetime',
                'required' => false,
                'input' => 'date',
                'sort_order' => 11,
                'group' => 'General Information',
            ]
        );
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
