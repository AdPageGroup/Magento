<?php

declare(strict_types=1);

namespace Tagging\GTM\Model\AgentFilter;

/**
 * The agent filter of the container: the User-Agent pieces to skip, and whether to skip browsers with traces of
 * automation (webdriver, HeadlessChrome, ...). Whatever comes in is checked again, because it ends up in a script
 * on every page.
 */
class Filter
{
    /**
     * @var string[]
     */
    private array $userAgents;

    private bool $automation;

    /**
     * @param mixed[] $userAgents Checked here: only valid pieces are kept
     */
    public function __construct(array $userAgents, bool $automation)
    {
        $this->userAgents = UserAgentList::clean($userAgents);
        $this->automation = $automation;
    }

    public static function none(): self
    {
        return new self([], false);
    }

    /**
     * @param mixed $data As the tagging domain or the cache gives it; anything unexpected counts as not set
     */
    public static function fromArray($data): self
    {
        if (!is_array($data)) {
            return self::none();
        }

        return new self(
            is_array($data['userAgents'] ?? null) ? $data['userAgents'] : [],
            ($data['automation'] ?? false) === true
        );
    }

    /**
     * @return array{userAgents: string[], automation: bool}
     */
    public function toArray(): array
    {
        return ['userAgents' => $this->userAgents, 'automation' => $this->automation];
    }

    /**
     * @return string[]
     */
    public function getUserAgents(): array
    {
        return $this->userAgents;
    }

    public function isAutomation(): bool
    {
        return $this->automation;
    }

    /**
     * True when nothing is filtered, and the page can stay as it was
     */
    public function isEmpty(): bool
    {
        return $this->userAgents === [] && !$this->automation;
    }
}
