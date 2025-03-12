<?php

namespace Fromholdio\RelativeURLField\Forms;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Parsers\URLSegmentFilter;
use SilverStripe\View\Requirements;

class RelativeURLField extends TextField
{
    protected ?string $baseURL = null;
    protected ?SiteTree $baseSiteTree = null;
    protected bool $isQueryStringAllowed = false;
    protected bool $isFullPathAllowed = true;
    protected bool $isCleanValueEnabled = true;
    protected ?string $defaultValue = null;
    protected array $collisionChecks = ['sitetree'];
    protected ?string $helpText = 'Special characters are automatically converted or removed.';

    private static $allowed_actions = [
        'suggest'
    ];


    public function suggest(HTTPRequest $request): string
    {
        $value = $request->getVar('value');
        $value = $this->cleanValue($value);

        if (empty($value)) {
            return $this->httpError(
                405,
                _t(__CLASS__ . '.EMPTY', 'Please enter a URL or click cancel')
            );
        }

        $valueParts = explode('?', $value);
        $value = $valueParts[0];
        $queryString = $valueParts[1] ?? null;

        $hasCollision = $this->runCollisionChecks($value);
        $count = 2;
        while ($hasCollision) {
            $newValue = $value . '-' . $count;
            $hasCollision = $this->runCollisionChecks($newValue);
            $count++;
        }
        if (!empty($newValue)) {
            $value = $newValue;
        }

        if ($this->getIsQueryStringAllowed() && !empty($queryString)) {
            $value .= '?' . $queryString;
        }

        Controller::curr()?->getResponse()?->addHeader('Content-Type', 'application/json');
        return json_encode(['value' => $value]);
    }


    public function getAttributes(): array
    {
        return array_merge(
            parent::getAttributes(),
            [
                'data-base-url' => $this->getBaseURL(),
                'data-default-value' => $this->getDefaultValue(),
                'data-allow-querystring' => $this->getIsQueryStringAllowed(),
                'data-allow-fullpath' => $this->getIsFullPathAllowed()
            ]
        );
    }


    public function Value(): string
    {
        return rawurldecode(parent::Value() ?? '');
    }

    public function setSubmittedValue($value, $data = null): self
    {
        $value = $this->cleanValue($value);
        return $this->setValue($value, $data);
    }

    protected function cleanValue(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $value = ltrim($value, '/');
        $valueParts = explode('#', $value);
        $value = $valueParts[0];

        $valueParts = explode('?', $value);
        $value = $valueParts[0];
        $queryString = $valueParts[1] ?? null;

        if ($this->getIsCleanValueEnabled()) {
            $filter = URLSegmentFilter::create();

            if ($this->getIsFullPathAllowed()) {
                $valueParts = array_filter(explode('/', $value));
                $filteredParts = [];
                foreach ($valueParts as $valuePart) {
                    $filteredParts[] = $filter->filter($valuePart);
                }
                $value = implode('/', $filteredParts);
            } else {
                $value = $filter->filter($value);
            }
        } else {
            $valueParts = explode('/', rtrim($value, '/'));
            if ($this->getIsFullPathAllowed()) {
                $value = implode('/', $valueParts);
            } else {
                $value = array_pop($valueParts);
            }
        }

        if ($this->getIsQueryStringAllowed() && !empty($queryString)) {
            $value .= '?' . $queryString;
        }

        $this->extend('updateCleanValue', $value);
        return $value;
    }

    public function setDefaultValue(?string $value): self
    {
        $this->defaultValue = $this->cleanValue($value);
        return $this;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }


    public function setBaseURL(?string $baseURL): self
    {
        $this->baseURL = $baseURL;
        return $this;
    }

    public function setBaseURLBySiteTree(?SiteTree $siteTree): self
    {
        $this->baseSiteTree = $siteTree;
        return $this;
    }

    public function getBaseSiteTree(): ?SiteTree
    {
        return $this->baseSiteTree;
    }

    public function getBaseURL(): ?string
    {
        $siteTree = $this->getBaseSiteTree();
        $url = $siteTree?->AbsoluteLink() ?? $this->baseURL;
        if (empty($url)) {
            $url = Director::absoluteBaseURL();
        }

        $urlParts = explode('?', $url);
        $url = $urlParts[0];
        $queryString = $urlParts[1] ?? null;

        $url = rtrim($url, '/') . '/';
        $this->extend('updateBaseURL', $url, $queryString);
        return $url;
    }


    public function getURL(): ?string
    {
        return Controller::join_links(
            $this->getBaseURL(),
            $this->Value()
        );
    }


    public function setIsQueryStringAllowed(bool $isAllowed): self
    {
        $this->isQueryStringAllowed = $isAllowed;
        return $this;
    }

    public function getIsQueryStringAllowed(): bool
    {
        return $this->isQueryStringAllowed;
    }

    public function setIsFullPathAllowed(bool $isAllowed): self
    {
        $this->isFullPathAllowed = $isAllowed;
        return $this;
    }

    public function getIsFullPathAllowed(): bool
    {
        return $this->isFullPathAllowed;
    }

    public function setIsCleanValueEnabled(bool $isCleanValueEnabled): self
    {
        $this->isCleanValueEnabled = $isCleanValueEnabled;
        return $this;
    }

    public function getIsCleanValueEnabled(): bool
    {
        return $this->isCleanValueEnabled;
    }

    public function setHelpText(?string $string): self
    {
        $this->helpText = $string;
        return $this;
    }

    public function getHelpText(): ?string
    {
        return $this->helpText;
    }


    public function addCollisionCheck(string $key): self
    {
        $checks = $this->collisionChecks;
        $checks[] = $key;
        $this->collisionChecks = array_values(array_combine($checks, $checks));
        return $this;
    }

    public function removeCollisionCheck(string $key): self
    {
        $checks = $this->collisionChecks;
        if (in_array($key, $checks)) {
            $checks = array_combine($checks, $checks);
            unset($checks[$key]);
            $checks = array_values($checks);
        }
        $this->collisionChecks = $checks;
        return $this;
    }

    public function setCollisionChecks(?array $keys): self
    {
        if (is_null($keys)) {
            $keys = null;
        }
        $this->collisionChecks = array_values(array_combine($keys, $keys));
        return $this;
    }

    public function getCollisionChecks(): array
    {
        return $this->collisionChecks;
    }

    protected function runCollisionChecks(?string $value): bool
    {
        $hasCollision = false;
        $checks = $this->getCollisionChecks();
        if (empty($checks)) {
            return $hasCollision;
        }
        if (in_array('sitetree', $checks)) {
            $hasCollision = $this->hasSiteTreeCollision($value);
        }
        if (!$hasCollision) {
            $this->extend('updateRunCollisionChecks', $hasCollision, $value, $checks);
        }
        return $hasCollision;
    }

    protected function hasSiteTreeCollision(?string $value): bool
    {
        if (empty($value)) {
            return false;
        }
        $baseSiteTree = $this->getBaseSiteTree();
        $link = empty($baseSiteTree) ? $value : Controller::join_links($baseSiteTree->Link(), $value);

        $rootID = ($multisitesClass = $this->getMultisitesClassName())
            ? $multisitesClass::inst()->getCurrentSiteId()
            : 0;

        $parts = preg_split('|/+|', $link ?? '');
        $URLSegment = array_shift($parts);
        $siteTree = SiteTree::get()->filter([
            'URLSegment' => $URLSegment,
            'ParentID' => $rootID
        ])->first();

        if ($siteTree && SiteTree::config()->get('nested_urls') && count($parts ?? [])) {
            foreach ($parts as $segment) {
                $next = SiteTree::get()->filter([
                    'URLSegment' => $segment,
                    'ParentID' => $siteTree->ID
                ])->first();

                if (!$next) {
                    break;
                }

                $siteTree->destroy();
                $siteTree = $next;
            }
        }

        // check that link matches, because SiteTree::get_by_link() might return parent page + "action"
        $hasCollision = $siteTree && trim(Director::makeRelative($siteTree->Link()), '/') == trim($link, '/');

        $this->extend('updateHasSiteTreeCollision', $hasCollision, $value);
        return $hasCollision;
    }

    public function getMultisitesClassName(): ?string
    {
        $manifest = ModuleLoader::inst()->getManifest();
        if ($manifest->moduleExists('symbiote/silverstripe-multisites')) {
            return \Symbiote\Multisites\Multisites::class;
        }
        if ($manifest->moduleExists('fromholdio/silverstripe-configured-multisites')) {
            return \Fromholdio\ConfiguredMultisites\Multisites::class;
        }
        return null;
    }

    public function Field($properties = [])
    {
        Requirements::css('fromholdio/silverstripe-relativeurlfield:client/css/RelativeURLField.css');
        Requirements::javascript('fromholdio/silverstripe-relativeurlfield:client/js/RelativeURLField.js');
        return parent::Field($properties);
    }


    public function Type(): string
    {
        return 'text relativeurl';
    }

    public function performReadonlyTransformation(): ReadonlyField
    {
        /** @var ReadonlyField $newInst */
        $newInst = parent::performReadonlyTransformation();
        $newInst->setHelpText($this->getHelpText());
        $newInst->setURLBaseSuffix($this->getURLBaseSuffix());
        $newInst->setDefaultURL($this->getDefaultURL());
        return $newInst;
    }
}
