<?php
namespace Webravolab\Cdn\Providers;

/**
 * class WebravoProvider
 *
 * Custom CDN provider for Webravo
 *
 * @author   Paolo Nardini <paolo.nardini@gmail.com>
 */
use Webravolab\Cdn\Providers\Contracts\CdnProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Log;

class WebravoProvider extends CdnAbstractProvider implements CdnProviderInterface
{
    protected $_configuration;
    protected $_cdn_url;
    protected $_cdn_upload_url;
    protected $_bypass = false;

    public function init($configuration) {
        $this->_configuration = $configuration;
        if (isset($configuration['bypass'])) {
            $this->_bypass = $configuration['bypass'];
        }
        if (isset($configuration['providers'])) {
            $providers = $configuration['providers'];
            if (isset($providers['Webravo'])) {
                if (isset($providers['Webravo']['url'])) {
                    $this->_cdn_url = $this->stripTrailingSlashFromPath($providers['Webravo']['url']);
                }
                if (isset($providers['Webravo']['upload_url'])) {
                    $this->_cdn_upload_url = $this->stripTrailingSlashFromPath($providers['Webravo']['upload_url']);
                }
            }
        }
        return $this;
    }

    /**
     * Return the current status of bypass flag
     * @return bool
     */
    public function bypass():bool
    {
        return $this->_bypass;
    }

    /**
     * Upload one single file to CDN
     *
     * @param $path         file path relative to public directory
     */
    public function upload($relative_path, $remote_path = null) {
        try {
            if ($this->_bypass) {
                return null;
            }
            Log::debug('Client: Uploading ' . $relative_path);
            if (!file_exists(public_path($relative_path))) {
                return null;
            }
            $a_parts = pathinfo($relative_path);
            if (!isset($a_parts['filename']) || !isset($a_parts['extension'])) {
                return null;
            }
            $fileName = $a_parts['filename'] . '.' . $a_parts['extension'];
            $local_url = $this->addTrailingSlashToPath(env('APP_URL')) . $this->stripLeadingSlashFromPath($relative_path);
            $url = $this->_cdn_upload_url . '?url=' . urlencode($local_url);
            if (!empty($remote_path)) {
                $url = $url . '&remote_url=' . urlencode($remote_path);
            } else {
                // Remote path is the same of local
                $remote_path = $relative_path;
            }
            $client = new Client();
            $options = [
                'allow_redirects' => false,
                'headers' => [
                    'User-Agent' => 'Bravo Assets Agent ' . env('LOCALE_VIEW') . ' 1.0',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8'
                ]
            ];
            $res = $client->request('GET', $url, $options);
            $status_code = $res->getStatusCode();
            if ($status_code == 301 || $status_code == 302) {
                // Try again following the redirect
                $url = $res->getHeader('Location')[0];
                $res = $client->request('GET', $url, $options);
                $status_code = $res->getStatusCode();
            }
            if ($status_code == 200) {
                // Return full path
                $remote_path = $this->_cdn_url . '/' . $this->stripLeadingSlashFromPath($remote_path);
                return $remote_path;
            }
            return null;
        }
        catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the full URL of the asset, with respect of the bypass flag
     *
     * @param $asset_path
     * @return string
     */
    public function getAssetUrl($asset_path) {
        if ($this->_bypass) {
            // Don't use CDN
            return $this->addTrailingSlashToPath(env('APP_URL')) . $this->stripLeadingSlashFromPath($asset_path);
        }
        else {
            return $this->_cdn_url . '/' . $this->stripLeadingSlashFromPath($asset_path);
        }
    }

    public function delete($assets) {
        // TODO
    }

    public function exists($assets):bool {
        // TODO
    }

    /*
     * Helper functions
     */

    protected function addTrailingSlashToPath($path) {
        if (substr($path,-1) != '/') {
            $path = $path . '/';
        }
        return $path;
    }

    protected function stripTrailingSlashFromPath($path) {
        if (substr($path,-1) == '/') {
            $path = substr($path,0,-1);
        }
        return $path;
    }

    protected function stripLeadingSlashFromPath($path) {
        if (substr($path,0,1) == '/') {
            $path = substr($path,1);
        }
        return $path;
    }

}