<?php

namespace MageOS\AutomaticTranslation\Block\Adminhtml\Product;

use Magento\Catalog\Block\Adminhtml\Product\Edit\Button\Generic;
use Magento\Framework\Registry;
use Magento\Ui\Component\Control\Container;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use Magento\Framework\View\Element\UiComponent\Context;

/**
 * Class TranslateButton
 * @package MageOS\AutomaticTranslation\Block\Adminhtml\Product
 */
class TranslateButton extends Generic
{

    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;

    /**
     * TranslateButton constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ModuleConfig $moduleConfig
    ) {
        $this->moduleConfig = $moduleConfig;
        parent::__construct($context, $registry);
    }

    /**
     * @return array
     */
    public function getButtonData(): array
    {
        $currentStore = $this->context->getRequestParam("store");
        if ($currentStore && intval($currentStore) !== 0) {
            if ($this->moduleConfig->isEnable($currentStore)) {

                return [
                    'label' => __('Translate'),
                    'class' => 'save primary',
                    'data_attribute' => [
                        'mage-init' => [
                            'buttonAdapter' => [
                                'actions' => [
                                    [
                                        'targetName' => 'product_form.product_form',
                                        'actionName' => 'save',
                                        'params' => [
                                            true,
                                            [
                                                'back' => 'edit',
                                                'translate' => true
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'class_name' => Container::SPLIT_BUTTON,
                    'options' => [
                        [
                            'label' => __('Switch translation scope'),
                            'data_attribute' => [
                                'mage-init' => [
                                    'buttonAdapter' => [
                                        'actions' => [
                                            [
                                                'targetName' => 'product_form.product_form.select_store_modal',
                                                'actionName' => 'toggleModal'
                                            ],
                                            [
                                                'targetName' => 'product_form.product_form.select_store_modal.translation_store_list',
                                                'actionName' => 'render'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'dropdown_button_aria_label' => __('Save options'),
                    'sort_order' => 100
                ];
            } else {
                return [];
            }
        }

        return [
            'label' => __('Translate'),
            'class' => 'save action-secondary',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'product_form.product_form.select_store_modal',
                                'actionName' => 'toggleModal'
                            ],
                            [
                                'targetName' => 'product_form.product_form.select_store_modal.translation_store_list',
                                'actionName' => 'render'
                            ]
                        ]
                    ]
                ]
            ],
            'on_click' => '',
            'sort_order' => 100
        ];
    }
}
