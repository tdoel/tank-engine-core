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
        $ntct = Array( "1" => "image/gif",
                       "2" => "image/jpeg",
                       "3" => "image/png",
                       "6" => "image/bmp",
                       "17" => "image/ico");
        $this->mime = $ntct[exif_imagetype(te_get_absolute_path($path))];
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
?>
