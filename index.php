<?php
$htpver="0.9.3";
$htphost=$_SERVER['HTTP_HOST'];
if (strpos($htphost,":")!==false) $htphost=substr($htphost,0,strpos($htphost,":"));
set_time_limit(0);
session_start();
include("dbconfig.php");

function mysqli_query_wrapper($dblink, $query, $bypass=false) {
  $log="\n<!-- $query";
  $time1=microtime(true);
  $kver=mysqli_query($dblink,$query);
  // uncomment this line to ditch the logs
  // $bypass=true
  if ($bypass==false) {
    $time2=microtime(true);
    echo $log;
    echo "\nTook: ".($time2-$time1);
    $afec=mysqli_affected_rows($dblink);
    if ($afec>=0) {
      echo ", affected: $afec";
    } else {
      echo ", error: ".mysqli_error($dblink);
    }
    echo " -->\n";
  }
  return $kver;
}

// kill cache to debug
//mysqli_query_wrapper($dblink,"SET SESSION query_cache_type = OFF");

$hashlistAlias="#HL#";
$myself=basename(__FILE__);
$cas=time();
$loadtime=microtime(true);
?>
<html>
<head>
  <title>Hashtopus <?php echo $htpver." [$htphost]"; ?></title>
  <link rel="icon" href="favicon.ico" type="image/x-icon"/>
  <link href='admin.css' rel='stylesheet' type='text/css'>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <!--<META http-equiv="cache-control" content="no-cache">-->
  <script type="text/javascript" src="jscolor/jscolor.js"></script>
  <script>
    function sourceChange(valu) {
      var pasteObject=document.getElementById("pasteLine");
      var uploadObject=document.getElementById("uploadLine");
      var importObject=document.getElementById("importLine");
      var downloadObject=document.getElementById("downloadLine");
      switch (valu) {
        case 'paste':
          pasteObject.style.display = '';
          uploadObject.style.display = 'none';
          importObject.style.display = 'none';
          downloadObject.style.display = 'none';
          break;
          
        case 'upload':
          pasteObject.style.display = 'none';
          uploadObject.style.display = '';
          importObject.style.display = 'none';
          downloadObject.style.display = 'none';
          break;
          
        case 'import':
          pasteObject.style.display = 'none';
          uploadObject.style.display = 'none';
          importObject.style.display = '';
          downloadObject.style.display = 'none';
          break;

        case 'url':
          pasteObject.style.display = 'none';
          uploadObject.style.display = 'none';
          importObject.style.display = 'none';
          downloadObject.style.display = '';
          break;
      }
    }
    function checkAll(formname, checktoggle)
    {
      var checkboxes = new Array(); 
      checkboxes = document.getElementById(formname).getElementsByTagName('input');
      for (var i=0; i<checkboxes.length; i++)  {
        if (checkboxes[i].type == 'checkbox')   {
          checkboxes[i].checked = checktoggle;
        }
      }
    }
  </script>
</head>
<body>
<?php
$_SESSION[$sess_name]=1;

if ($_SESSION[$sess_name]==1)
{
  
  // define redirect place
  if (isset($_POST["return"])) {
    $returnpage=$_POST["return"];
  } else {
    $returnpage="";
  }

  // create the menu
  echo '<table class="big"><tr><td><a href="'.$myself.'"><img src="img/logo.png" alt="Hashtopus"></a><br><ul>
<li><a href="admin.php">Admin Login</a></li>
<br>
<li><a href="'.$myself.'?a=releases">Hashcat releases</a> (<a href="'.$myself.'?a=newrelease">new</a>)</li>';

if (file_exists("custmenu.php")) {
  // create custom menu, should it be present
  // refer to custmenu file for its documentation in comments
  include("custmenu.php");
  $custmenu=true;
  echo "<br>";
  foreach ($custmenuitems as $action=>$menuitem) {
    if ($menuitem["condition"]) {
      echo "<li><a href=\"$myself?a=custmenu&menu=$action\">".$menuitem["name"]."</a></li>";
    }
  }
}

echo '</ul>
<hr>
<ul>
<li><a href="'.$myself.'?a=manual">Manual</a></li>
</ul>v'.$htpver.'</td><td>';
  // correct password
  $platforms=array("unknown","NVidia","AMD");
  //$workloads=array(1=>"Low utilization",2=>"Default profile",3=>"High utilization");
  $oses=array("<img src=\"img/win.png\" alt=\"Win\" title=\"Windows\">","<img src=\"img/unix.png\" alt=\"Unix\" title=\"Linux\">");
  $states=array("New","Init","Running","Paused","Exhausted","Cracked","Aborted","Quit","Bypass","Trimmed","Aborting...");
  $formats=array("Text","HCCAP","Binary","Superhashlist");
  $formattables=array("hashes","hashes_binary","hashes_binary");
  $uperrs=array("","Uploaded file is too big for server settings, use different transfer method (i.e. FTP) and run directory scan in Task detail","Uploaded file is too big for form setting. Have you been playing with admin again?!","File upload was interrupted","No file was uploaded","","Server doesn't have a temporary folder","Failed writing to disk. Maybe no space left or >2GB file on FAT32","Some PHP module stopped the transfer");
  // Add new task as default
  // new task form
      $oname="";
      $oattack="";
      $ochunk=$config["chunktime"];
      $ostatus=$config["statustimer"];
      $oadjust=0;
      $hlist="";
      $color="";
      if (isset($_GET["task"])) {
        $orig=intval($_GET["task"]);
        if ($orig>0) {
          $ori=mysqli_query_wrapper($dblink,"SELECT name,attackcmd,chunktime,statustimer,autoadjust,hashlist,color FROM tasks WHERE id=$orig");
          if ($erej=mysqli_fetch_array($ori,MYSQLI_ASSOC)) {
            $oname=$erej["name"]." (copy)";
            $oattack=$erej["attackcmd"];
            $ochunk=$erej["chunktime"];
            $ostatus=$erej["statustimer"];
            $oadjust=$erej["autoadjust"];
            $hlist=$erej["hashlist"];
            $color=$erej["color"];
            if ($hlist=="") $hlist="preconf";
          } else {
            $orig=0;
          }
        }
      }
      echo "<table><tr><td>";
      echo "Create new task:";
      echo "<form action=\"$myself?a=newtaskp\" method=\"POST\" enctype=\"multipart/form-data\">";
      echo "<table class=\"styled\">";
      echo "<tr><td>Property</td><td>Value</td></tr>";
      echo "<tr><td>Name:</td><td><input type=\"text\" name=\"name\" value=\"$oname\"></td></tr>";
      echo "<tr><td>Hashlist:</td><td><select name=\"hashlist\">";
      $hlists=array(""=>"(please select)","preconf"=>"(pre-configured task)");
      $kver=mysqli_query_wrapper($dblink,"SELECT id,name FROM hashlists ORDER BY id ASC");
      while($erej=mysqli_fetch_array($kver,MYSQLI_ASSOC)) {
        $hlists[$erej["id"]]=$erej["name"];
      }
      foreach ($hlists as $hlid=>$hlname) {
        echo "<option value=\"$hlid\"".($hlid==$hlist ? " selected" : "").">$hlname</option>";
      }
      echo "</select> (hashlist needs to be created before task)</td></tr>";
      echo "<tr><td>Command line:</td><td><textarea name=\"cmdline\" cols=\"64\" id=\"cmdLine\">$oattack</textarea><br>";
      echo "Use <b>$hashlistAlias</b> for hash list and assume all files in current directory.<br>If you have Linux agents, please mind the filename case sensitivity!<br>Also, don't use any of these parameters, they will be invoked automatically:<br>hash-type, limit, outfile-check-dir, outfile-check-timer, potfile-disable, remove,<br>remove-timer, separator, session, skip, status, status-timer</td></tr>";
      echo "<tr><td>Chunk size:</td><td><input type=\"text\" name=\"chunk\" value=\"$ochunk\"> seconds</td></tr>";
      echo "<tr><td>Status timer:</td><td><input type=\"text\" name=\"status\" value=\"$ostatus\"> seconds</td></tr>";
      echo "<tr><td>Benchmark:</td><td><input type=\"checkbox\" name=\"autoadjust\" value=\"1\"".($oadjust==1 ? " checked" : "")."> Auto adjust<br>(Not recommended for AMD and/or in combination with small chunks sizes)</td></tr>";
      echo "<tr><td>Color:</td><td>#<input type=\"text\" name=\"color\" size=\"6\" class=\"color {required:false}\" value=\"$color\"></td></tr>";
      echo "<tr><td colspan=\"2\"><input type=\"submit\" value=\"Create task\"></td></tr>";
      echo "</table>";
      echo "</td>";
      echo "<td>Add new files:<br>";
      echo "<form action=\"$myself?a=filesp\" method=\"POST\" enctype=\"multipart/form-data\">";
      echo "<input type=\"hidden\" name=\"source\" value=\"upload\">";
      echo "<table class=\"styled\" id=\"upfiles\">";
      echo "<tr><td>Upload files <button type=\"button\" onclick=\"javascript:addLine('upfiles');\">Add file</button></td></tr>";
      echo "<tr><td><input type=\"submit\" value=\"Upload files\"></td></tr>";
      echo "</table>";
      echo "</form><br>";
      echo "Attach files:";
      echo "<script>function assignFile(cmdLine,addObject,fileName) { if (fileName.indexOf('.7z') != -1) fileName=fileName.substring(0,fileName.length-2)+'???'; var cmdObject = document.getElementById(cmdLine); if (addObject == true) { if (cmdObject.value.indexOf(fileName) == -1) { if (cmdObject.value.length>0 && cmdObject.value.slice(-1)!=' ') cmdObject.value += ' '; cmdObject.value += fileName; } } else { cmdObject.value = cmdObject.value.replace(fileName,''); while (cmdObject.value.slice(-1)==' ') cmdObject.value=cmdObject.value.substring(0,cmdObject.value.length-1); while (cmdObject.value.substring(0,1)==' ') cmdObject.value=cmdObject.value.substring(1); } }</script>";
      echo "<table class=\"styled\">";
      echo "<tr><td>Filename</td><td>Size</td></tr>";
      if ($orig>0) {
        $kver=mysqli_query_wrapper($dblink,"SELECT files.*,SIGN(IFNULL(taskfiles.task,0)) AS che FROM files LEFT JOIN taskfiles ON taskfiles.file=files.id AND taskfiles.task=$orig ORDER BY filename ASC");
      } else {
        $kver=mysqli_query_wrapper($dblink,"SELECT files.*,0 AS che FROM files ORDER BY filename ASC");
      }
      while($erej=mysqli_fetch_array($kver,MYSQLI_ASSOC)) {
        $fid=$erej["id"];
        echo "<tr><td><input type=\"checkbox\" name=\"adfile[]\" value=\"$fid\" onChange=\"javscript:assignFile('cmdLine',this.checked,'".$erej["filename"]."');\"";
        if ($erej["che"]==1) echo " checked";
        echo ">".$erej["filename"];
        if ($erej["secret"]==1) echo " <img src=\"img/lock.gif\" alt=\"Secret\">";
        echo "</td><td>".nicenum($erej["size"])."B</td></tr>";
      }
      echo "</table>";
      echo "</form>";
      echo "</td></tr></table>";

  switch ($_GET["a"])
  {
      
  }
  // if there is someplace to return, go there
  if ($returnpage!="") echo "<script>location.href='$myself?$returnpage';</script>";

} else {
  echo "<form action=\"$myself?a=\" method=\"POST\"><input type=\"hidden\" name=\"return\" value=\"".$_SERVER['QUERY_STRING']."\">Password: <input type=\"password\" name=\"pwd\" autofocus><input type=\"submit\" value=\"Login\"></form>";
}

function makepwd($pwd) {
  // password hashing function (how ironic:D)
  return sha1("l3Bsn@^auh28".$pwd."m+3RuKngLy\$t0alT");
}

function shortenstring($co,$kolik) {
  // shorten string that would be too long
  echo "<span title=\"$co\">";
  if (strlen($co)>$kolik) {
    echo substr($co,0,$kolik-3)."...";
  } else {
    echo $co;
  }
  echo "</span>";
}

function niceround($num,$dec) {
  // round to specific amount of decimal places
  $stri=strval(round($num,$dec));
  if ($dec>0) {
    $pozice=strpos($stri,".");
    if ($pozice===false) {
      $stri.=".00";
    } else {
      while (strlen($stri)-$pozice<=$dec) $stri.="0";
    }
  }
  return $stri;
  
}

function nicenum($num,$treshold=1024,$divider=1024) {
  // display nicely formated number divided into correct units
  $r=0;
  while ($num>$treshold) {
    $num/=$divider;
    $r++;
  }
  $rs=array("","k","M","G");
  $vysnew=niceround($num,2);
  return $vysnew." ".$rs[$r];

}

function uploadFile($tmpfile,$source,$sourcedata) {
  // upload file from multiple sources
  global $uperrs;
  
  $povedlo=false;
  echo "<b>Adding file $tmpfile:</b><br>";
  if (!file_exists($tmpfile)) {
    switch ($source) {
      case "paste":
        echo "Creating file from text field...";
        if (file_put_contents($tmpfile,$sourcedata)) {
          echo "OK";
          $povedlo=true;
        } else {
          echo "ERROR!";
        }
        break;
          
      case "upload":
        $hashfile=$sourcedata;
        $hashchyba=$hashfile["error"];
        if ($hashchyba==0) {
          echo "Moving uploaded file...";
          if (move_uploaded_file($hashfile["tmp_name"],$tmpfile) && file_exists($tmpfile)) {
            echo "OK";
            $povedlo=true;
          } else {
            echo "ERROR";
          }
        } else {
          echo "Upload file error: ".$uperrs[$hashchyba];
        }
        break;
      
      case "import":
        echo "Loading imported file...";
        if (file_exists("import/".$sourcedata)) {
          rename("import/".$sourcedata,$tmpfile);
          if (file_exists($tmpfile)) {
            echo "OK";
            $povedlo=true;
          } else {
            echo "DST ERROR";
          }
        } else {
          echo "SRC ERROR";
        }
        break;
        
      case "url":
        $local=basename($sourcedata);
        echo "Downloading remote file <a href=\"$sourcedata\" target=\"_blank\">$local</a>...";

        $furl=fopen($sourcedata,"rb");
        if (!$furl) {
          echo "SRC ERROR";
        } else {
          $floc=fopen($tmpfile,"w");
          if (!$floc) {
            echo "DST ERROR";
          } else {
            $downed=0;
            $bufsize=131072;
            $cas_pinfo=time();
            while (!feof($furl)) {
              if (!$data=fread($furl,$bufsize)) {
                echo "READ ERROR";
                break;
              }
              fwrite($floc,$data);
              $downed+=strlen($data);
              if ($cas_pinfo<time()-10) {
                echo nicenum($downed,1024)."B...\n";
                $cas_pinfo=time();
                flush();
              }
            }
            fclose($floc);
            echo "OK (".nicenum($downed,1024)."B)";
            $povedlo=true;
          }
          fclose($furl);
        }
        break;

      default:
        echo "Wrong file source.";
    }
  } else {
    echo "File already exists.";
  }
  echo "<br>";
  return $povedlo;
}

function insertFile($tmpfile) {
  // insert existing file into global files
  global $dblink;
  $allok=false;
  if (file_exists($tmpfile)) {
    $velikost=filesize($tmpfile);
    $nazev=mysqli_real_escape_string($dblink,basename($tmpfile));
    echo "Inserting <a href=\"$tmpfile\" target=\"_blank\">$nazev</a> into global files...";
    if (mysqli_query_wrapper($dblink,"INSERT INTO files (filename,size) VALUES ('$nazev',$velikost)")) {
      $fid=mysqli_insert_id($dblink);
      echo "OK (<a href=\"$myself?a=files#$fid\">list</a>)";
      $allok=true;
    } else {
      echo "DB ERROR";
    }
  }
  echo "<br>";
  return $allok;
}

function tickdone($prog,$total) {
  // show tick of progress is done
  if ($total>0 && $prog==$total) {
    return " <img src=\"img/check.png\" alt=\"Finished\">";
  }
  return "";
}

function showperc($part,$total,$decs=2) {
  // show nicely formated percentage
  if ($total>0) {
    $vys=round(($part/$total)*100,$decs);
    if ($vys==100 && $part<$total) {
      $vys-=1/(10^$decs);
    }
    if ($vys==0 && $part>0) {
      $vys+=1/(10^$decs);
    }
  } else {
    $vys=0;
  }
  $vysnew=niceround($vys,$decs);
  return $vysnew;
}

function superList($hlist,&$format) {
  // detect superhashlists and create array of its contents
  global $dblink;
  
  if ($format==3) {
    $superhash=true;
  } else {
    $superhash=false;
  }
  
  $hlistar=array();
  if ($superhash) {
    $kve=mysqli_query_wrapper($dblink,"SELECT hashlists.id,hashlists.format FROM superhashlists JOIN hashlists ON superhashlists.hashlist=hashlists.id WHERE superhashlists.id=$hlist");
    while($ere=mysqli_fetch_array($kve,MYSQLI_ASSOC)) {
      $format=$ere["format"];
      $hlistar[]=$ere["id"];
    }
  } else {
    $hlistar[]=$hlist;
  }
  $hlisty=implode(",",$hlistar);
  return array($superhash,$hlisty);
}

function sectotime($soucet) {
  // convert seconds to human readable format
  $vysledek="";
  if ($soucet>86400) {
    $dnu=floor($soucet/86400);
    if ($dnu>0) $vysledek.=$dnu."d ";
    $soucet=$soucet%86400;
  }
  $vysledek.=gmdate("H:i:s",$soucet);
  return $vysledek;
}

function delete_task($task) {
  // delete task
  global $dblink;
  $vysledek1=mysqli_query_wrapper($dblink,"DELETE FROM assignments WHERE task=$task");
  $vysledek2=$vysledek1 && mysqli_query_wrapper($dblink,"DELETE FROM errors WHERE task=$task");
  $vysledek3=$vysledek2 && mysqli_query_wrapper($dblink,"DELETE FROM taskfiles WHERE task=$task");

  $vysledek4=$vysledek3 && mysqli_query_wrapper($dblink,"UPDATE hashes JOIN chunks ON hashes.chunk=chunks.id AND chunks.task=$task SET chunk=NULL");
  $vysledek5=$vysledek4 && mysqli_query_wrapper($dblink,"UPDATE hashes_binary JOIN chunks ON hashes_binary.chunk=chunks.id AND chunks.task=$task SET chunk=NULL");
  $vysledek6=$vysledek5 && mysqli_query_wrapper($dblink,"DELETE FROM zapqueue WHERE chunk IN (SELECT id FROM chunks WHERE task=$task)");
  $vysledek7=$vysledek6 && mysqli_query_wrapper($dblink,"DELETE FROM chunks WHERE task=$task");

  $vysledek8=$vysledek7 && mysqli_query_wrapper($dblink,"DELETE FROM tasks WHERE id=$task");
  
  return ($vysledek8);
}

function delete_agent($agent) {
  // delete agent
  global $dblink;

  $vysledek1=mysqli_query_wrapper($dblink,"DELETE FROM assignments WHERE agent=$agent");
  $vysledek2=$vysledek1 && mysqli_query_wrapper($dblink,"DELETE FROM errors WHERE agent=$agent");
  $vysledek3=$vysledek2 && mysqli_query_wrapper($dblink,"DELETE FROM hashlistusers WHERE agent=$agent");
  $vysledek4=$vysledek3 && mysqli_query_wrapper($dblink,"DELETE FROM zapqueue WHERE agent=$agent");

  // orphan the chunks
  $vysledek5=$vysledek4 && mysqli_query_wrapper($dblink,"UPDATE hashes JOIN chunks ON hashes.chunk=chunks.id AND chunks.agent=$agent SET chunk=NULL");
  $vysledek6=$vysledek5 && mysqli_query_wrapper($dblink,"UPDATE hashes_binary JOIN chunks ON hashes_binary.chunk=chunks.id AND chunks.agent=$agent SET chunk=NULL");
  $vysledek7=$vysledek6 && mysqli_query_wrapper($dblink,"UPDATE chunks SET agent=NULL WHERE agent=$agent");

  $vysledek8=$vysledek7 && mysqli_query_wrapper($dblink,"DELETE FROM agents WHERE id=$agent");
  
  return ($vysledek8);
}

$endtime=microtime(true);
echo "<!-- Load time: ".($endtime-$loadtime)."ms -->";
?>
</td></tr></table>
</body></html>
