    <title>CodeChecker</title>
</head>
<body>

<header class="main-header">
    <nav class="constant-nav clearfix">
        <ul>
            <li class="nav-cc"><a href="?key=action&val=projects">CodeChecker</a></li> 
        </ul>
    </nav>
</header>


<div class="main-content cf">

    <h1>CodeChecker</h1>

<!--
    <div class="dashboard-actions">
        <ul class="dashboard-links">
            <li><a href="#">Add a new project</a></li>
            <li><a href="#">Add a new standard</a></li>
        </ul>
    </div>
-->

<?php
echo '<div class="dashboard-projects"><ul class="dashboard-links">';
foreach ($codeCheck->cfg['projects'] as $id => $details) {
    $standard = (isset($details['type']) && $details['type'] !== '') ? $details['type'] : $codeCheck->defaultStandard;
    $standardName = $codeCheck->cfg['standards'][$standard]['name'];
    $standardName = $standardName === null ? '???' : $standardName;
    echo '<li><a href="?key=project&val='.$id.'">'.$details['name'].'<br/><span class="standard-name">'.$standardName.'</span></a></li>'."\n";
}
echo '</ul></div>';

?>
</div>

<div id="overlay-bg" class="overlay-bg"></div>

<div class="overlay confirm-overlay" id="checkGeneric">
    <p class="overlay-close">
        <a href="#">Close window</a>
    </p>
	<div class="confirm-text">
        <h1>Confirmation Required</h1>
        <p>Are you sure?</p>
        <ul class="overlay-actions">
            <li><a href="#" class="btn btn-no"><span>No</span></a></li>
            <li><a href="#" class="btn btn-yes"><span>Yes</span></a></li>
        </ul>
    </div>
</div>