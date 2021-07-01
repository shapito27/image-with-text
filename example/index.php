<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <title>Add text on image</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <style>
        #images-wrapper {
            width: 100%;
            display: flex;
        }

        .images {
            width: 50%;
        }
    </style>
</head>
<body>
<div class="container">
    <main>
        <div class="py-5 text-center">
            <h1>Place text on image</h1>
            <p class="lead">Tool helps to add text on image</p>
        </div>

        <form action="" method="post" enctype="multipart/form-data" id="generate-image" class="row">
            <h4>Image settings</h4>
            <div class="row mb-3">
                <label for="image">Image</label>
                <input class="form-control" type="file" id="image" name="image" accept="image/*"
                       onchange="loadFile(event)">
            </div>
            <div class="row mb-3">
                <label for="text-size">Image quality</label>
                <input class="form-control" type="number" name="image-quality" id="image-quality" value="100"
                       step="1">
            </div>
            <div class="row mb-3">
                <label for="text">Text</label>
                <input class="form-control" type="text" name="text" id="text">
            </div>
            <h4 class="mb-3">Text settings</h4>
            <div class="row mb-3">
                <label for="text-size" class="form-label">Size</label>
                <input class="form-control" type="number" name="text-size" id="text-size" value="22" max="200" min="8"
                       step="0.5">
            </div>
            <div class="row mb-3">
                <label for="text-size" class="form-label">Coefficient Left Right Padding, % </label>
                <input class="form-control" type="number" name="text-coefficient-left-right"
                       id="text-coefficient-left-right" value="20" max="100" min="0" step="1">
            </div>
            <div class="row mb-3">
                <label for="text-size" class="form-label">Coefficient top bottom padding for each line, % </label>
                <input class="form-control" type="number" name="text-coefficient-top-bottom"
                       id="text-coefficient-top-bottom" value="15" max="100" min="0" step="1">
            </div>
            <div class="row mb-3">
                <label for="text-color" class="form-label">Color</label>
                <input class="form-control form-control-color" type="color" name="text-color" id="text-color"
                       value="#000000"
                       title="Choose color of text">
            </div>
            <input class="btn btn-primary" name="submit" type="submit" value="Generate">
        </form>
        <div>
            <div id="errors" style="color: #ff2d6f"></div>
            <div id="images-wrapper">
                <div class="images"><img id="preview"/></div>
                <div class="images"><img id="result-image"/></div>
            </div>
        </div>
    </main>
</div>
<script>
    /**
     * Show preview
     */
    var loadFile = function (event) {
        var preview = document.getElementById('preview');
        preview.src = URL.createObjectURL(event.target.files[0]);
        preview.onload = function () {
            URL.revokeObjectURL(preview.src) // free memory
        }
    };
</script>
<script>
    var form = document.getElementById('generate-image');
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        var xhttp = new XMLHttpRequest();
        var formData = new FormData(form);
        // FETCH FILEIST OBJECTS
        var image = document.getElementById('image').files;
        xhttp.onreadystatechange = function () {
            console.log(this);
            if (this.readyState === 4 && this.status === 200) {
                try {
                    var obj = JSON.parse(this.response);
                    if (obj.status === true) {
                        document.getElementById('result-image').src = obj.result;
                    } else {
                        document.getElementById('errors').innerText = obj.error;
                    }
                } catch (error) {
                    throw Error;
                }
            }
        };

        xhttp.open('POST', '/example/endpoint-create-image.php', true);
        xhttp.send(formData);
    });
</script>
</body>
</html>
