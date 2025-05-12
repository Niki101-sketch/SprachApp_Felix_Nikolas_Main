<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Karteikarten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Karteikarten - Unit <?php echo $_GET['unit'] ?? '1'; ?></h1>
        
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card text-center">
                    <div class="card-body" style="height: 200px; display: flex; align-items: center; justify-content: center;">
                        <h2 id="word">Familie</h2>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <button class="btn btn-secondary" onclick="flip()">Umdrehen</button>
                    <button class="btn btn-success" onclick="correct()">Richtig</button>
                    <button class="btn btn-danger" onclick="wrong()">Falsch</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let isGerman = true;
        let currentIndex = 0;
        
        const words = [
            {german: "Familie", english: "Family"},
            {german: "Mutter", english: "Mother"},
            {german: "Vater", english: "Father"}
        ];
        
        function flip() {
            const wordElement = document.getElementById('word');
            if (isGerman) {
                wordElement.textContent = words[currentIndex].english;
            } else {
                wordElement.textContent = words[currentIndex].german;
            }
            isGerman = !isGerman;
        }
        
        function correct() {
            next();
        }
        
        function wrong() {
            next();
        }
        
        function next() {
            currentIndex = (currentIndex + 1) % words.length;
            document.getElementById('word').textContent = words[currentIndex].german;
            isGerman = true;
        }
    </script>
</body>
</html>