<?php

namespace Drupal\Core\Routing;

use Drupal\Component\Utility\UrlHelper;

/**
 * Provides a trait which ensures that a URL is safe to redirect to.
 */
trait LocalAwareRedirectResponseTrait {

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * {@inheritdoc}
   */
  protected function isLocal($url) {
    return !UrlHelper::isExternal($url)
      || UrlHelper::externalIsLocal($url, $this->getRequestContext()->getCompleteBaseUrl())
      || in_array(parse_url($url, PHP_URL_HOST), \Drupal::moduleHandler()->invokeAll('local_domains'), TRUE);
  }

  /**
   * Returns the request context.
   *
   * @return \Drupal\Core\Routing\RequestContext
   */
  protected function getRequestContext() {
    if (!isset($this->requestContext)) {
      $this->requestContext = \Drupal::service('router.request_context');
    }
    return $this->requestContext;
  }

  /**
   * Sets the request context.
   *
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   *
   * @return $this
   */
  public function setRequestContext(RequestContext $request_context) {
    $this->requestContext = $request_context;

    return $this;
  }

}
