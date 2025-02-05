<?php
namespace exface\Core\Facades;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use kabachello\FileRoute\FileRouteMiddleware;
use Psr\Http\Message\UriInterface;
use kabachello\FileRoute\Templates\PlaceholderFileTemplate;
use exface\Core\Facades\AbstractHttpFacade\NotFoundHandler;
use exface\Core\DataTypes\StringDataType;
use exface\Core\CommonLogic\Filemanager;
use function GuzzleHttp\Psr7\stream_for;
use exface\Core\Facades\DocsFacade\MarkdownDocsReader;
use exface\Core\Facades\DocsFacade\Middleware\AppUrlRewriterMiddleware;
use exface\Core\Facades\AbstractHttpFacade\HttpRequestHandler;
use exface\Core\Facades\AbstractHttpFacade\AbstractHttpFacade;

/**
 *  
 * @author Andrej Kabachnik
 *
 */
class DocsFacade extends AbstractHttpFacade
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractHttpFacade\AbstractHttpFacade::createResponse()
     */
    protected function createResponse(ServerRequestInterface $request) : ResponseInterface
    {
        $handler = new HttpRequestHandler(new NotFoundHandler());
        
        // Add URL rewriter: it will take care of URLs after the content had been generated by the router
        $handler->add(new AppUrlRewriterMiddleware($this));
        
        $requestUri = $request->getUri();
        $baseUrl = StringDataType::substringBefore($requestUri->getPath(), '/' . $this->buildUrlToFacade(true), '');
        $baseUrl = $requestUri->getScheme() . '://' . $requestUri->getAuthority() . $baseUrl;
        
        $baseRewriteRules = $this->getWorkbench()->getConfig()->getOption('FACADES.DOCSFACADE.BASE_URL_REWRITE');
        if (! $baseRewriteRules->isEmpty()) {
            foreach ($baseRewriteRules->getPropertiesAll() as $pattern => $replace) {
                $baseUrl = preg_replace($pattern, $replace, $baseUrl);
            }
        }
        
        // Add router middleware
        $matcher = function(UriInterface $uri) {
            $path = $uri->getPath();
            $url = StringDataType::substringAfter($path, '/' . $this->buildUrlToFacade(true), '');
            $url = ltrim($url, "/");
            $url = urldecode($url);
            if ($q = $uri->getQuery()) {
                $url .= '?' . $q;
            }
            return $url;
        };
        
        $reader = new MarkdownDocsReader($this->getWorkbench());
        $templatePath = Filemanager::pathJoin([$this->getApp()->getDirectoryAbsolutePath(), 'Facades/DocsFacade/template.html']);
        $template = new PlaceholderFileTemplate($templatePath, $baseUrl . '/' . $this->buildUrlToFacade(true));
        $template->setBreadcrumbsRootName('Documentation');
        $handler->add(new FileRouteMiddleware($matcher, $this->getWorkbench()->filemanager()->getPathToVendorFolder(), $reader, $template));
        
        $response = $handler->handle($request);
        foreach ($this->buildHeadersCommon() as $header => $val) {
            $response = $response->withHeader($header, $val);
        }
        return $response;
    }
    
    protected function buildHeadersCommon() : array
    {
        return array_filter($this->getConfig()->getOption('FACADES.DOCSFACADE.HEADERS.COMMON')->toArray());
    }
    
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractHttpFacade\AbstractHttpFacade::getUrlRouteDefault()
     */
    public function getUrlRouteDefault(): string
    {
        return 'api/docs';
    }
}