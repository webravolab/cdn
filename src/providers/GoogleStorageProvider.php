<?php
namespace Webravolab\Cdn\Providers;

/**
 * class GoogleStorageProvider
 *
 * Custom CDN provider for Google Storage Bucket
 *
 * @author   Paolo Nardini <paolo.nardini@gmail.com>
 */

use Webravo\Persistence\Service\CdnService;
use Webravo\Persistence\Service\ConfigurationService;
use Webravo\Infrastructure\Library\DependencyBuilder;
use Webravolab\Cdn\Providers\Contracts\CdnProviderInterface;
use Webravo\Infrastructure\Service\CdnServiceInterface;

class GoogleStorageProvider extends CdnAbstractProvider implements CdnProviderInterface
{
    protected $_cdn_service = null;
    protected $_log_service = null;
    protected $_configuration;
    protected $_cdn_url;
    protected $_cdn_upload_url;
    protected $_bypass = false;
    protected $_overwrite = true;       // overwrite missing images - default true
    protected $_check_size = false;     // check changed images also by file size - default false
    protected $_bucket = null;
    protected $_ttl = 86400;

    public function init($configuration) {
        $this->_configuration = $configuration;
        if (isset($configuration['bypass'])) {
            $this->_bypass = $configuration['bypass'];
        }
        if (isset($configuration['overwrite'])) {
            $this->_overwrite = $configuration['overwrite'];
        }
        if (isset($configuration['checksize'])) {
            $this->_check_size = $configuration['checksize'];
        }
        if (isset($configuration['default']) && $configuration['default'] == 'GoogleStorage') {
            // Get configuration overrides
            if (isset($configuration['providers'])) {
                $providers = $configuration['providers'];
                if (isset($providers['GoogleStorage'])) {
                    if (isset($providers['GoogleStorage']['bucket'])) {
                        $this->_bucket = $providers['GoogleStorage']['bucket'];
                    }
                    if (isset($providers['GoogleStorage']['ttl'])) {
                        $this->_ttl = $providers['GoogleStorage']['ttl'];
                    }
                }
            }
        }

        // Initialize Google CDN Service
        $this->_cdn_service = DependencyBuilder::resolve('Webravo\Infrastructure\Service\CdnServiceInterface');
        $this->_log_service = DependencyBuilder::resolve('Psr\Log\LoggerInterface');
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
     * Return the current status of overwrite flag
     * @return bool
     */
    public function overwrite():bool
    {
        return $this->_overwrite;
    }

    /**
     * Return the current status of check_size flag
     * @return bool
     */
    public function checksize():bool
    {
        return $this->_check_size;
    }


    /**
     * Upload one single file to CDN
     *
     * @param $path         file path relative to public directory
     */
    public function upload($path, $remote_path = null) {
        try {
            if ($this->_bypass) {
                return null;
            }
            // Log::debug('CDN Client: Uploading ' . $path);
            $this->_log_service->debug('[WebravoLab][Cdn][GoogleStorageProvider][Upload] Uploading ' . $path);
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
                    $this->_log_service->warning('[WebravoLab][Cdn][GoogleStorageProvider][Upload] invalid relative path ' . $path);
                    $relative_path = $path;
                }
            }
            else {
                $this->_log_service->error('[WebravoLab][Cdn][GoogleStorageProvider][Upload] invalid source path ' . $path);
                return null;
            }
            $a_parts = pathinfo($absolute_path);
            if (!isset($a_parts['filename']) || !isset($a_parts['extension'])) {
                return null;
            }
            $fileName = $a_parts['filename'] . '.' . $a_parts['extension'];
            if (empty($remote_path)) {
                $remote_path = $relative_path;
            }
            $remote_path = $this->_cdn_service->uploadImageToCdn($absolute_path, $remote_path, $this->_bucket);

            return $remote_path;
        }
        catch (\Exception $e) {
            $this->_log_service->error('[WebravoLab][Cdn][GoogleStorageProvider][Upload] upload error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the full URL of the asset, with respect of the bypass flag
     *
     * @param $asset_path
     * @return string
     */
    public function getAssetUrl($asset_path)
    {
        if ($this->_bypass) {
            // Don't use CDN
            return $this->addTrailingSlashToPath(env('APP_URL')) . $this->stripLeadingSlashFromPath($asset_path);
        }
        else {
            // TODO
            return $this->_cdn_url . '/' . $this->stripLeadingSlashFromPath($asset_path);
        }
    }

    public function delete($remote_path):bool
    {
        try {
            $this->_log_service->error('[WebravoLab][Cdn][GoogleStorageProvider][Delete] delete remote file ' . $remote_path);
            return $this->_cdn_service->deleteImageFromCdn($remote_path, $this->_bucket);
        }
        catch (\Exception $e) {
            $this->_log_service->error('[WebravoLab][Cdn][GoogleStorageProvider][Delete] delete error: ' . $e->getMessage());
            return false;
        }
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