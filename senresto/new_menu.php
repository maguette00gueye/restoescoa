<?php include  "config/database.php";  ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ajouter un nouvelle Menu </title>
  <!-- Lien vers Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
    body {
      background-color: #f3f4f6;
      font-family: 'Open Sans', sans-serif;
      padding-top: 50px;
    }

    .container {
      max-width: 800px;
      padding: 30px;
      border-radius: 10px;
      background-color: #fff;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .card {
      border: none;
      border-radius: 15px;
    }

    .form-control {
      border-radius: 8px;
      box-shadow: none;
    }

    .form-label {
      font-weight: 600;
      color: #333;
    }

    .btn-submit {
      background-color: #28a745;
      color: white;
      border-radius: 30px;
      font-weight: 600;
      padding: 12px 25px;
      width: 100%;
      transition: background-color 0.3s ease;
    }

    .btn-submit:hover {
      background-color: #218838;
    }

    .custom-file-input {
      padding: 10px;
      border-radius: 8px;
    }

    .input-group-text {
      border-radius: 8px;
    }

    .image-preview {
      max-width: 100%;
      height: auto;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .image-container {
      margin-top: 20px;
      text-align: center;
    }

    .image-preview-container {
      display: none;
    }

    .image-preview-container.show {
      display: block;
    }

  </style>
</head>
<body>

  <div class="container">
    <h2 class="text-center mb-4">Ajouter un Menu </h2>

    <div class="card shadow">
      <div class="card-body">
        <form action="ajounew_menu.php" method="POST" enctype="multipart/form-data">
          
          <div class="mb-4">
            <label for="name" class="form-label">Nom du Menu </label>
            <input type="text" class="form-control" id="name" name="name" placeholder="Entrez le nom du plat " required>
          </div>

          <div class="mb-4">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Entrez une description du plat... " required></textarea>
          </div>

          <div class="mb-4">
            <label for="price" class="form-label">Prix</label>
            <input type="text" class="form-control" id="price" name="price" placeholder="Entrez le prix du plat " required>
          </div>

          <div class="mb-4">
              <label for="date" class="form-label">Date</label>
              <input type="date" class="form-control" id="date" name="date" required>
          </div>

          <!-- Champ Heure -->
          <div class="mb-4">
              <label for="heure" class="form-label">Heure</label>
              <input type="time" class="form-control" id="heure" name="heure" required>
          </div>
          
          <div class="mb-4">
            <label for="categorie" class="form-label">Catégorie</label>
            <select class="form-control" id="categorie" name="categorie" required>
              <option value="Petit_dejeuner">Petit_dejeuner </option>
              <option value="repas">repas </option>
              <option value="diner">diner </option>
              <option value="plat_special">Plat_Special</option>
              <option value="#">#</option>
            </select>
          </div>

          <div class="mb-4">
            <label for="image" class="form-label"> Image du Menu </label>
            <div class="input-group">
              <input type="file" class="form-control custom-file-input" id="image" name="image" accept="image/*" required>
              <label class="input-group-text" for="image">Choisir une image</label>
            </div>
            <div class="image-container image-preview-container mt-3">
              <img id="imagePreview" class="image-preview" alt="Image prévisualisée">
            </div>
          </div>

          <button type="submit" class="btn btn-submit">Ajouter le Menu  </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Lien vers Bootstrap JS et Popper.js -->
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

  <!-- Script pour prévisualiser l'image -->
  <script>
    const fileInput = document.getElementById('image');
    const imagePreviewContainer = document.querySelector('.image-preview-container');
    const imagePreview = document.getElementById('imagePreview');

    fileInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      const reader = new FileReader();

      reader.onload = function() {
        imagePreview.src = reader.result;
        imagePreviewContainer.classList.add('show');
      }

      if (file) {
        reader.readAsDataURL(file);
      } else {
        imagePreviewContainer.classList.remove('show');
      }
    });
  </script>

</body>
</html>
