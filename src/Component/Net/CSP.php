<?php
namespace Lite\Component\Net;

/**
 * Content Security Policy
 * references site:
 * etc: Content-Security-Policy:default-src 'self' *.abc.com
 * https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy
 */
abstract class CSP{
	const CSP_KEY = 'Content-Security-Policy';
	const CSP_REPORT_KEY = 'Content-Security-Policy-Report-Only';

	//directive list
	const CSP_DIRECTIVE_DEFAULT_SRC = 'default-src';
	const CSP_DIRECTIVE_CONTENT_SRC = 'content-src';
	const CSP_DIRECTIVE_IMAGE_SRC = 'img-src';
	const CSP_DIRECTIVE_STYLE_SRC = 'style-src';
	const CSP_DIRECTIVE_SCRIPT_SRC = 'script-src';
	const CSP_DIRECTIVE_FONT_SRC = 'font-src';
	const CSP_DIRECTIVE_IFRAME_SRC = 'iframe-src';
	const CSP_DIRECTIVE_MANIFEST_SRC = 'manifest-src';
	const CSP_DIRECTIVE_MEDIA_SRC = 'media-src';
	const CSP_DIRECTIVE_OBJECT_SRC = 'object-src';
	const CSP_DIRECTIVE_BASE_URI = 'base-uri';
	const CSP_DIRECTIVE_FORM_ACTION = 'form-action';

	//some fixed pattern
	//self define patter like wildcard, host name, url, uri: *.abc.com
	const CSP_PATTERN_SELF = "'self'";
	const CSP_PATTERN_NONE = "'none'";
	const CSP_PATTERN_UNSAFE_INLINE = "'unsafe-inline'";
	const CSP_PATTERN_HTTP = 'http:';
	const CSP_PATTERN_HTTPS = 'https:';

	/**
	 * send policies header
	 * @param $policies
	 * <p>Example:
	 * sendCSPPolicies([
	 *      [self::CSP_DIRECTIVE_DEFAULT_SRC, self::CSP_PATTERN_SELF],
	 *      [self::CSP_DIRECTIVE_IMAGE_SRC, [self::CSP_PATTERN_SELF, '*.google.com'],
	 *      [self::CSP_DIRECTIVE_STYLE_SRC, [self::CSP_PATTERN_SELF, self::CSP_PATTERN_UNSAFE_INLINE],
	 * ]);
	 * </p>
	 */
	public static function sendCSPPolicies($policies){
		header(self::CSP_KEY.':'.static::buildCSPPolicies($policies));
	}

	/**
	 * send policies header & report violation situation
	 * @param $policies
	 * @param $report_uri
	 */
	public static function sendCSPPoliciesWithReport($policies, $report_uri){
		header(self::CSP_REPORT_KEY.':'.static::buildCSPPolicies($policies)."; report-uri {$report_uri}");
	}

	/**
	 * build policies
	 * @param $policies
	 * @return string
	 */
	public static function buildCSPPolicies($policies){
		$directives = [];
		foreach($policies as list($directive, $patterns)){
			$directives[] = static::buildCSPPolicy($directive, $patterns);
		}

		return join(';', $directives);
	}

	/**
	 * build policy
	 * @param string $directive
	 * @param array|string $patterns
	 * @return string
	 */
	public static function buildCSPPolicy($directive, $patterns){
		if(is_array($patterns)){
			$patterns = join(' ', $patterns);
		}
		return "$directive $patterns";
	}
}