<?php
namespace Webravolab\Cdn;

/**
 * Class CdnController
 *
 * @author   Paolo Nardini <paolo.nardini@gmail.com>
 */

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Cdn;
use Illuminate\Support\Facades\Input;
use Log;

class CdnController extends Controller
{
    /**
     * CDN server only: upload a remote asset to a local path
     *
     * @return string
     */
    public function uploadAsset()
    {
        try {
            $url = Input::get('url');
            if (empty($url)) {
                return 'KO-1';
            }
            $local_path = Input::get('remote_url', $url);

            Log::debug('CDN: Uploaded file ' . $url . ' as ' . $local_path);
            $binary_content = file_get_contents($url);

            parse_str($local_path, $vars);
            $a_parts = parse_url($local_path);
            if (isset($a_parts['path'])) {
                $directory = public_path(dirname($a_parts['path']));
                if (!is_dir($directory)) {
                    @mkdir($directory, 0777, true);
                }
                $local_path = public_path($a_parts['path']);
                if (file_exists($local_path)) {
                    rename($local_path, $local_path . '-old-' . time());
                    // unlink($local_path);
                }
                file_put_contents($local_path, $binary_content);
                return 'OK';
            }
            return 'KO-2';
        }
        catch (\Exception $e) {
            return 'KO-3';
        }
    }
}