document.addEventListener('DOMContentLoaded', function() {
    var textarea = document.getElementById("flux-post-content");
    if (textarea) {
        textarea.addEventListener("input", function(){
            this.style.height = "auto";
            this.style.height = (this.scrollHeight) + "px";
        });
        textarea.addEventListener("keydown", function(e){
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                textarea.readOnly = true;
                textarea.style.opacity = "0.5";
                var submitStatus = document.getElementById("submit-status");
                if (submitStatus) {
                    submitStatus.style.display = "block";
                }
                setTimeout(() => {
                    this.form.submit();
                }, 100);
            }
        });
    }
});
