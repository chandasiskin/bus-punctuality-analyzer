<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Character Encoding -->
    <meta charset="UTF-8">
    
    <!-- Responsive Viewport -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Title -->
    <title>Progresser</title>
    
    <!-- SEO -->
    <meta name="description" content="Bus punctuality analyzer">
    <meta name="author" content="Lauri">

    <!-- Favicon -->
    <link rel="icon" href="./img/favicon.png" type="image/png">

    <!-- CSS -->
    <link rel="stylesheet" href="./css/normalize.css">
    <link rel="stylesheet" href="./css/dropzone.min.css">
    <link rel="stylesheet" href="./css/style.css">
    
    <!-- Google-stuff -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@500&display=swap">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="Bus punctuality analyzer" />
    <meta property="og:description" content="Automatically validate and calculate delays in bus trafic." />
    <meta property="og:image" content="https://ritarisolutions.com/progresser/img/favicon_1200x630.png" />
    <meta property="og:url" content="https://ritarisolutions.com/progresser" />
    <meta property="og:type" content="website" />
    <meta property="og:locale" content="en_US" />

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Bus punctuality analyzer" />
    <meta name="twitter:description" content="Automatically validate and calculate delays in bus trafic." />
    <meta name="twitter:image" content="https://ritarisolutions.com/progresser/img/favicon_1200x675.png" />
    <meta name="twitter:url" content="https://ritarisolutions.com/progresser" />
</head>
<body>
    
    <script src="./js/dropzone.min.js"></script>

    <form action="./file-upload.php" class="dropzone" enctype="multipart/form-data" id="upload-form">
        <div class="dz-message">
            <span class="material-symbols-outlined">attach_file</span><br />
            Drag and drop your file here<br />
            <small>or browse</small>
        </div>
    </form>
    <div class="dz-preview"></div>
    
    <!-- Loading overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-spinner">
            <span class="material-symbols-outlined">sync</span>
            <p>Processing your file...</p>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="./js/script.js"></script>

</body>
</html>