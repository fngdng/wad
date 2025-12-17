// js/gallery.js
$(document).ready(function(){
    $(".gallery-img").click(function(){
        var info = $(this).data("info");
        $("#gallery-info").html("<strong>Checkpoint Info:</strong> " + info).fadeIn();
    });
    
    // Accordion - simple slideToggle
    $(".accordion-header").click(function(){
        $(this).closest('.accordion-item').find('.accordion-content').slideToggle(300);
        $(this).closest('.accordion-item').toggleClass('active');
    });
});