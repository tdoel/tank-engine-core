<?php
class te_rescource Extends te_reply
{
  private $path = "";
  private $mime = "";

  public function __construct($path, $mime = null)
  {
    if(!te_file_exists($path))
    {
      tank_engine::throw(WARNING, "Rescource ".$path. " seems to be missing.");
    }
    else
    {
      $this->path = $path;
      if($mime)
      {
        $this->mime = $mime;
      }
      else
      {
        //mime type not given, so try to figure it out based on the file extension

        //known mime types
        $mime_types = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            'docx' => 'application/msword',
            'xlsx' => 'application/vnd.ms-excel',
            'pptx' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if(isset($mime_types[$ext]))
        {
          $this->mime = $mime_types[$ext];
        }
        else
        {
          $this->mime = 'application/octet-stream';
        }
      }
    }
  }
  public function render_reply()
  {
    if($this->path != "")
    {
      Header("Content-type: ".$this->mime);
      $fp = fopen(te_get_absolute_path($this->path),"rb");
      fpassthru($fp);
      fclose($fp);
      return true;
    }
    else
    {
      tank_engine::throw(ERROR,"Rescource ".$this->path." could not be fetched.");
      return false;
    }
  }
}
