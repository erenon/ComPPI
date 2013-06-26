<?php

namespace Comppi\DownloadCenterBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class DownloadCenterController extends Controller
{
    private $releases_dir = './dbreleases/'; // trailing slash is important!
    private $current_db_sql = 'comppi.sql';
    
    public function currentReleaseGuiAction()
    {
        $path = $this->releases_dir.$this->current_db_sql;
        $T = array();
        if (file_exists($path)) {
            $T['size'] = number_format((filesize($path)/1048576), 2, '.', ' '); // get it in MB
            $T['mtime'] = date("Y-m-d. H:i:s", filemtime($path));
        }
        return $this->render('DownloadCenterBundle:DownloadCenter:currentrelease.html.twig', $T);
    }
    
    public function currentReleaseAction()
    {
        return $this->serveFile($this->releases_dir.$this->current_db_sql);
    }
    
    public function customDownloadsGuiAction()
    {
        
    }
    
    public function previousReleasesGuiAction()
    {
        $T = array(
            'ls' => array()
        );
        
        $d = dir($this->releases_dir);
        while (false !== ($entry = $d->read())) {
            if ($entry!='.' && $entry!='..' && $entry!=$this->current_db_sql)
            {
                $entry = array(
                    'file' => $entry,
                    'size' => number_format((filesize($this->releases_dir.$entry)/1048576), 2, '.', ' '),
                    'mtime' => date("Y-m-d. H:i:s", filemtime($this->releases_dir.$entry))
                );
                $T['ls'][$entry['mtime']] = $entry;
            }
        }
        $d->close();
        
        ksort($T['ls']);
        
        return $this->render('DownloadCenterBundle:DownloadCenter:previousreleases.html.twig', $T);
    }
    
    public function previousReleaseAction($file)
    {
        return $this->serveFile($this->releases_dir.$file); // $this->releases_dir prevents cross-site serving!
    }
    
    
    private function listDirectory($dir)
    {
        
    }
    
    private function serveFile($filepath)
    {
        if (file_exists($filepath))
        {
            $response = new Response();
            $this->createFileservingHeaders($response, basename($filepath));
            // stream_copy_to_stream() or file_get_contents() would be nicer, but that is a memory hog
            // Symfony2.1's StreamedResponse is not available in 2.0
            $response->sendHeaders();
            ob_clean();
            flush();
            readfile($filepath);
            exit();
        } else {
            throw new NotFoundException();
        }
    }
    
    private function createFileservingHeaders(Response $response, $filename)
    {
        session_cache_limiter('none');
		
		$response->headers->set('Content-Description', 'File Transfer');
		//$response->headers->set('Cache-Control', 'public');
		//$response->headers->set('Cache-Control', 'must-revalidate');
		$response->headers->set('Cache-Control', 'no-cache'); // this is the official
		$response->headers->set('Pragma', 'public');
		$response->headers->set('Content-Type', 'application/octet-stream');
		$response->headers->set('Content-Transfer-Encoding', 'binary');
		$response->headers->set('Expires', '0');
		$response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }
}
