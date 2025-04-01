<?php

namespace MageOS\AutomaticTranslation\Setup\Patch\Data;

use Magento\Catalog\Model\Category;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use MageOS\AutomaticTranslation\Api\AttributeProviderInterface as AttributeProvider;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean as AttributeBoolean;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validator\ValidateException;

/**
 * Class AddSkipTranslationCategoryAttribute
 * @package MageOS\AutomaticTranslation\Setup\Patch\Data
 */
class AddSkipTranslationCategoryAttribute implements DataPatchInterface
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
     * AddSkipTranslationCategoryAttribute constructor.
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

        $eavSetup->removeAttribute(Category::ENTITY, AttributeProvider::SKIP_TRANSLATION);
        $eavSetup->addAttribute(
            Category::ENTITY,
            AttributeProvider::SKIP_TRANSLATION,
            [
                'visible_on_front' => false,
                'visible' => true,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'label' => AttributeProvider::SKIP_TRANSLATION_LABEL,
                'source' => AttributeBoolean::class,
                'type' => 'int',
                'required' => false,
                'input' => 'boolean',
                'sort_order' => 10,
                'group' => 'General Information',
                'note' => AttributeProvider::SKIP_TRANSLATION_NOTE
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
