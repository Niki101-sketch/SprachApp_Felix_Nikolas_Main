<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Units</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
         .nav-item {
            margin: 0.5rem 0;
        }
        .nav-link {
            border-radius: 0.5rem;
            transition: all 0.3s;
        }
        .nav-link:hover {
            background-color: #0d6efd;
            color: white !important;
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <!-- Navigation Bar für Login/Registrieren -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index2.php">SprachApp</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>
    <div class="container mt-4">
        <h1>Units</h1>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>Unit 1: Familie</h5>
                        <p>25 Vokabeln</p>
                        <a href="karteikarten.php?unit=1" class="btn btn-primary">Start</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>Unit 2: Haus</h5>
                        <p>30 Vokabeln</p>
                        <a href="karteikarten.php?unit=2" class="btn btn-primary">Start</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>Unit 3: Schule</h5>
                        <p>22 Vokabeln</p>
                        <a href="karteikarten.php?unit=3" class="btn btn-primary">Start</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>