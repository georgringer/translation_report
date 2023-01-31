<?php

declare(strict_types=1);

namespace GeorgRinger\TranslationReport\Controller;

use GeorgRinger\TranslationReport\Provider\TranslationProvider;
use GeorgRinger\TranslationReport\Repository\TranslationRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TranslationReportController
{
    public function __construct(
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly IconRegistry $iconRegistry,
        private readonly IconFactory $iconFactory,
    )
    {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);


        $mainView = $queryParams['action'] ?? $backendUserUc['action'] ?? 'index';

        switch ($mainView) {
            case 'index':

                return $this->indexAction($request);
            case 'import':
                return $this->importAction($request);
            default:
                throw new \Exception('Unknown action');
        }
    }

    protected function indexAction($request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $this->registerDocHeaderButtons($view, $request->getAttribute('normalizedParams')->getRequestUri());

        $repository = GeneralUtility::makeInstance(TranslationRepository::class);
        $view->assignMultiple([
            'count' => $repository->countAll(),
            'duplicates' => $repository->getMostDuplicated(10),
        ]);

        return $view->renderResponse('Report/Index');
    }

    protected function importAction($request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $this->registerDocHeaderButtons($view, $request->getAttribute('normalizedParams')->getRequestUri());

        $provider = GeneralUtility::makeInstance(TranslationProvider::class);
        $provider->fillDatabase();

        return new RedirectResponse($this->uriBuilder->buildUriFromRoute(
            'translation_report',
            [
                'action' => 'index',
            ]
        ));

    }


    protected function registerDocHeaderButtons(ModuleTemplate $view, string $requestUri): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();

        // Create new
        $import = $buttonBar->makeLinkButton()
            ->setHref((string)$this->uriBuilder->buildUriFromRoute(
                'translation_report',
                [
                    'action' => 'import',
                ]
            ))
            ->setShowLabelText(true)
            ->setTitle('Import')
            ->setIcon($this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL));
        $buttonBar->addButton($import, ButtonBar::BUTTON_POSITION_LEFT, 10);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

}
