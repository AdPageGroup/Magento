<?php declare(strict_types=1);

namespace Tagging\GTM\Test\Integration\DataLayer\Tag;

use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Tagging\GTM\DataLayer\Tag\Version;

class VersionTest extends TestCase
{
    public function testIfVersionIsSemantic()
    {
        $version = ObjectManager::getInstance()->get(Version::class);
        // SemVer 2.0.0 pattern that supports pre-release identifiers, plus an optional fourth
        // numeric segment because hotfix releases are tagged as X.Y.Z.N (for example 1.9.3.1)
        // Matches: X.Y.Z, X.Y.Z.N, X.Y.Z-alpha1, X.Y.Z-beta1, X.Y.Z-rc1, etc.
        $semverPattern = '/^([0-9]+)\.([0-9]+)\.([0-9]+)(?:\.([0-9]+))?(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?$/';
        $this->assertTrue((bool)preg_match($semverPattern, $version->get()));
    }
}
