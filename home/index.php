<?php include_once dirname(__DIR__) . '/layout/header.php'; ?>

<main class="container mt-5">
    <h2 class="text-center">Welcome to EnDeryp &COPY;</h2>
    <p class="text-center">Choose an option from the menu to get started.</p>

    <div class="text-center mt-4">
        <video aria-label="Welcome greeting video" class="img-thumbnail img-fluid" autoplay loop muted loading="lazy" style="border-radius: 15px; overflow: hidden; max-height: 450px;">
            <source src="../asset/media/greet.mp4" type="video/mp4">
            Your browser does not support the video tag.
            <p>Video could not be loaded. Please check back later.</p>
        </video>
      
        <!-- <img src="../output.gif" alt="" class="img-fluid"> -->
    </div>
</main>

<?php include_once dirname(__DIR__) . '/layout/footer.php'; ?>