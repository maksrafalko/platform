<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\SetHttpAllowHeader;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\ApiActions;

/**
 * Sets "Allow" HTTP header if the response status code is 405 (Method Not Allowed).
 * In case if there are no any allowed HTTP methods, the response status code is changed to 404.
 */
class SetHttpAllowHeaderForSubresource extends SetHttpAllowHeader
{
    /**
     * {@inheritdoc}
     */
    protected function getHttpMethodToActionsMap()
    {
        return [
            self::METHOD_GET => ApiActions::GET_SUBRESOURCE
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExcludeActions(Context $context)
    {
        /** @var SubresourceContext $context */

        return $this->getExcludeActionsForClass(
            $context->getParentClassName(),
            $context->getVersion(),
            $context->getRequestType()
        );
    }
}
