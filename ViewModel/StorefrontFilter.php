<?php declare(strict_types=1);

namespace Tagging\GTM\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Tagging\GTM\Model\Botfilter\BotfilterConfig;
use Tagging\GTM\Model\Botfilter\BotfilterKey;
use Tagging\GTM\Model\Botfilter\RulesStore;
use Tagging\GTM\Model\Botfilter\SkipScript;

/**
 * For script.phtml and iframe.phtml: the script that sets `window.trytagging_skip` for a
 * browser or page on the storefront rules of the AdPage Botfilter. Empty without a key or
 * without rules, and then the templates render exactly what they did before.
 */
class StorefrontFilter implements ArgumentInterface
{
    private BotfilterConfig $botfilterConfig;
    private RulesStore $store;
    private ?string $script = null;

    public function __construct(BotfilterConfig $botfilterConfig, RulesStore $store)
    {
        $this->botfilterConfig = $botfilterConfig;
        $this->store = $store;
    }

    public function getSkipScript(): string
    {
        if ($this->script === null) {
            $this->script = $this->build();
        }

        return $this->script;
    }

    private function build(): string
    {
        try {
            $key = BotfilterKey::parse($this->botfilterConfig->getKey());
            return $key === null ? '' : SkipScript::render($this->store->rulesFor($key->getContainerId()));
        } catch (\Throwable $e) {
            return '';
        }
    }
}
