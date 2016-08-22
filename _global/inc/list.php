<?php
$project = $codeCheck->cfg['projects'][$_SESSION['current']['project']];
$_SESSION['current']['folder'] = $_SESSION['current']['folder'] === null ? $project['base'] : $_SESSION['current']['folder'];
$folder = $_SESSION['current']['folder'];
$parent = $_SESSION['current']['parent'];
?>
    <title><?php echo $project['name']; ?> | CodeChecker</title>
    <link rel="stylesheet" href="_global/css/app-list.css">
</head>
<body>

<header class="main-header">
    <nav class="constant-nav clearfix">
        <ul>
            <li class="nav-cc"><a href="?key=action&val=projects">CodeChecker</a></li> 
            <li class="nav-visit-site"><a href="?key=folder&val=<?php echo $project['base']; ?>"><?php echo $project['name']; ?></a></li>
        </ul>
    </nav>
</header>

<div class="main-content cf">

    <h1><?php echo $project['name']; ?> <span>(<a href="<?php echo '?edit=explorer+'.urlencode($codeCheck->cfg['standards'][$project['type']]['file']).''; ?>"><?php echo $codeCheck->cfg['standards'][$project['type']]['name']; ?></a>)</span></h1>

    <div id="table-holder-explorer" class="table-holder">
        <?php $codeCheck->outputCurrentPath($folder, $project); ?>
        <?php $contents = $codeCheck->fileServer->getFolderContents($folder); ?>
        <?php $codeCheck->outputFolderContents($folder, $project, $contents); ?>
    </div>

</div>