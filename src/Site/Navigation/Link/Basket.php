<?php
namespace Basket\Site\Navigation\Link;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\Navigation\Link\LinkInterface;
use Omeka\Stdlib\ErrorStore;

class Basket implements LinkInterface
{
    public function getName()
    {
        return 'Basket'; // @translate
    }

    public function getFormTemplate()
    {
        return 'common/navigation-link-form/label';
    }

    public function isValid(array $data, ErrorStore $errorStore)
    {
        if (!isset($data['label'])) {
            $errorStore->addError('o:navigation', sprintf('Invalid navigation: link without label (%s)', $this->getName())); // @translate
            return false;
        }
        return true;
    }

    public function getLabel(array $data, SiteRepresentation $site)
    {
        return isset($data['label']) && trim($data['label']) !== ''
            ? $data['label']
            : 'Basket'; // @translate
    }

    public function toZend(array $data, SiteRepresentation $site)
    {
        return [
            'label' => $data['label'],
            'route' => 'site/basket',
            'class' => 'basket-link',
            'params' => [
                'site-slug' => $site->slug(),
            ],
        ];
    }

    public function toJstree(array $data, SiteRepresentation $site)
    {
        return [
            'label' => isset($data['label']) ? trim($data['label']) : '',
        ];
    }
}
