<?php namespace App\Controllers;

/**
 * CDN Dynamic Image Controller
 *
 * "I treat my works as my own child, be careful with my childrens"
 *
 * Created with love and proud by Ghivarra Senandika Rushdie
 *
 * @package CI4 Dynamic Image
 *
 * @var https://github.com/ghivarra
 * @var https://facebook.com/bcvgr
 * @var https://twitter.com/ghivarra
 *
**/

use CodeIgniter\Files\File;
use Config\Services;
use Intervention\Image\ImageManager;

class ImageController extends BaseController
{
    protected $allowedImageType = ['image/png', 'image/jpeg', 'image/bmp', 'image/webp'];

    protected $maxWidth = 2048;
    protected $maxHeight = 2048;
    protected $options = ['height', 'width', 'forced'];

    //=====================================================================================

    public function index()
    {
        $all = func_get_args();

        if (empty($all))
        {
            $this->response->setStatusCode(404)->setJSON([
                'code'    => 404,
                'status'  => 'Not Found',
                'message' => 'Image not found'
            ]);
        }

        $path = FCPATH . 'dist' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $all);

        // check file
        if (!file_exists($path))
        {
            $this->response->setStatusCode(404)->setJSON([
                'code'    => 404,
                'status'  => 'Not Found',
                'message' => 'Image not found'
            ]);
        }

        $file = new File($path);
        $mime = $file->getMimeType();

        if (!in_array($mime, $this->allowedImageType))
        {
            $this->response->setStatusCode(403)->setJSON([
                'code'    => 403,
                'status'  => 'Forbidden',
                'message' => 'Image must be of type png, jpg, jpeg, bmp or webp'
            ]);
        }

        // get params from client
        $get = [
            'width'    => $this->request->getGet('width'),
            'height'   => $this->request->getGet('height'),
            'priority' => $this->request->getGet('priority')
        ];

        // set etag
        $etag = '"'. md5($this->request->getServer('REQUEST_URI')) .'"';

        // get image size
        $imageInfo = getimagesize($path);
        $params = [
            'width'    => isset($get['width']) ? (($get['width'] > $this->maxWidth) ? $this->maxWidth : $get['width']) : $imageInfo[0],
            'height'   => isset($get['height']) ? (($get['height'] > $this->maxHeight) ? $this->maxHeight : $get['height']) : $imageInfo[1],
            'priority' => isset($get['priority']) ? (in_array($get['priority'], $this->options) ? $get['priority'] : 'width') : 'width'
        ];

        // if
        if (($params['width'] == $imageInfo[0]) && ($params['height'] == $imageInfo[1]))
        {
            $this->response->setHeader('Cache-Control', 'max-age=31536000, immutable');
            $this->response->setHeader('Content-Length', filesize($path));
            $this->response->setHeader('Vary', 'Accept-Encoding');
            $this->response->setHeader('ETag', $etag);

            // return
            return $this->response->setContentType($mime)->setBody(file_get_contents($path));
        }

        // get image library
        $imageManipulator = new ImageManager();

        // create image
        $img = $imageManipulator->make($path);

        // set resize
        switch ($params['priority']) {
            case 'width':
                $img->resize($params['width'], NULL, function ($constraint) {
                    $constraint->aspectRatio();
                });
                break;

            case 'height':
                $img->resize(NULL, $params['height'], function ($constraint) {
                    $constraint->aspectRatio();
                });
                break;

            case 'forced':
                $img->resize($params['width'], $params['height']);
                break;
            
            default:
                return $this->response->setStatusCode(400)->setJSON([
                    'code'    => 400,
                    'status'  => 'Bad Request',
                    'message' => 'Wrong image priority'
                ]);
                break;
        }

        $this->response->setHeader('Cache-Control', 'max-age=31536000, immutable');
        $this->response->setHeader('Content-Length', $img->filesize());
        $this->response->setHeader('Vary', 'Accept-Encoding');
        $this->response->setHeader('ETag', $etag);

        // return
        return $this->response->setContentType($img->mime())->setBody($img->response());
    }

    //=====================================================================================
}