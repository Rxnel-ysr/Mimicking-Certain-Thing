<?php includeView("layout/header"); ?>
<main class="container mt-5">
    <h2 class="text-center">Welcome to Native-PHP &COPY;</h2>
    <p class="text-center">Choose an option from the menu to get started.</p>

    <div class="text-center mt-4">
        <!-- terlalu beratss -->
        <!-- <video aria-label="Welcome greeting video" class="img-thumbnail img-fluid" autoplay loop muted loading="lazy" style="border-radius: 15px; overflow: hidden; max-height: 450px;">
            <source src="<?= asset("media/greet.mp4") ?>" type="video/mp4">
            Your browser does not support the video tag.
            <p>Video could not be loaded. Please check back later.</p>
        </video> -->


        <img src="<?= asset("media/Levi.gif") ?>" alt="" class="img-thumbnail img-fluid" style="border-radius: 15px; overflow: hidden; max-height: 450px;">
    </div>
</main>
<?php includeView("layout/footer"); ?>