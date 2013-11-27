<?php
/**
 * Super-simple extension of Drew McLellan’s original mailchimp-api class. This
 * provides file-based caching of API data
 * 
 * Requires mailchimp-api (https://github.com/drewm/mailchimp-api)
 * 
 * @author Bobby Jack <bobby@theday.co.uk>
 * @version 1.0
 */
class MailChimpCached extends MailChimp
{
	private $cache_dir = '/tmp';

	/**
	 * Time, in seconds, to cache data from specific methods
	 */
	private $method_lifetimes = array(
		'campaigns/list' => 3600,
		'campaigns/content' => 600
	);

	/**
	 * Call an API method. Every request needs the API key, so that is added automatically -- you don't need to pass it in.
	 * @param  string $method The API method to call, e.g. 'lists/list'
	 * @param  array  $args   An array of arguments to pass to the method. Will be json-encoded for you.
	 * @return array          Associative array of json decoded API response.
	 */
	public function call($method, $args=array())
	{
		$dir = $this->cache_dir.'/'.$method;

		if (!file_exists($dir))
		{
			mkdir($dir, 0777, true);
		}

		$file = $dir.'/'.md5(join(',', array_merge(array_keys($args), array_values($args))));

		if (file_exists($file) && time() - filemtime($file) <
			(array_key_exists($method, $this->method_lifetimes) ? $this->method_lifetimes[$method]
				: 60))
		{
			return json_decode(file_get_contents($file), true);
		}
		else
		{
			$result = parent::call($method, $args);
			file_put_contents($file, json_encode($result));
			return $result;
		}
	}
}
