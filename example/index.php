<style>
    #images-wrapper{
        width: 100%;
        display: flex;
    }
    .images{
        width: 50%;
    }
</style>
<form action="" method="post" enctype="multipart/form-data" id="generate-image">
    <label for="image">Image <input type="file" id="image" name="image" accept="image/*"
                                    onchange="loadFile(event)"></label>
    <br>
    <label for="text-size">Image quality <input type="number" name="image-quality" id="image-quality" value="100"
                                                max="100" min="0"></label>
    <br>
    <label for="text">Text <input type="text" name="text" id="text"></label>
    <br>
    Text settings
    <br>
    <label for="text-size">Size <input type="number" name="text-size" id="text-size" value="22"></label>
    <br>
    <label for="text-size">Coefficient Left Right Padding, % <input type="number" name="text-coefficient-left-right"
                                                                    id="text-coefficient-left-right" value="20"></label>
    <br>
    <label for="text-size">Coefficient Top Bottom Padding, % <input type="number" name="text-coefficient-top-bottom"
                                                                    id="text-coefficient-top-bottom" value="15"></label>
    <br>
    <label for="text-color">Color <input type="color" name="text-color" id="text-color" value="#000000"></label>
    <br>
    <input name="submit" type="submit" value="Generate">
</form>
<div>
    <div id="errors" style="color: #ff2d6f"></div>
    <div id="images-wrapper">
        <div class="images"><img id="preview"/></div>
        <div class="images"><img id="result-image"/></div>
    </div>
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
    var form = document.getElementById("generate-image");
    form.addEventListener("submit", (event) => {
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

        xhttp.open("POST", "/example/endpoint-create-image.php", true);
        xhttp.send(formData);
    });
</script>
