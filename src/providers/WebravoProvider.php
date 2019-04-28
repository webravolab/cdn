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
    protected $_bypass_assets = false;
    protected $_overwrite = true;       // overwrite missing images - default true
    protected $_check_size = false;     // check changed images also by file size - default false

    public function init($configuration) {
        $this->_configuration = $configuration;
        if (isset($configuration['bypass'])) {
            $this->_bypass = $configuration['bypass'];
        }
        if (isset($configuration['bypass_assets'])) {
            $this->_bypass_assets = $configuration['bypass_assets'];
        }
        if (isset($configuration['overwrite'])) {
            $this->_overwrite = $configuration['overwrite'];
        }
        if (isset($configuration['checksize'])) {
            $this->_check_size = $configuration['checksize'];
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
     * Upload one single file to CDN
     *
     * @param $path             file path absolute or relative to public directory
     * @param $remote_path      remote uploaded file path (relative to cdn root)
     */
    public function upload($path, $remote_path = null) {
        try {
            if ($this->_bypass) {
                return null;
            }
            Log::debug('CDN Client: Uploading ' . $path);
            if (file_exists(public_path($path))) {
                $absolute_path = public_path($path);
                $relative_path = $path;
            }
            elseif (file_exists($path)) {
                $absolute_path = $path;
                $public_pos = mb_strpos($path, 'public/');
                if ($public_pos !== false) {
                    $relative_path = mb_substr($path, $public_pos + 6);
                }
                else {
                    Log::warning('CDN Client: invalid relative path ' . $path);
                    $relative_path = $path;
                }
            }
            else {
                Log::critical('CDN Client: invalid source path ' . $path);
                return null;
            }
            $a_parts = pathinfo($absolute_path);
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
            Log::error('WebravoProvider / upload Error:' . $e->getMessage());
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

    public function delete($remote_path):bool {
        // TODO
        return false;
    }

    public function exists($assets):bool {
        // TODO
        return false;
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