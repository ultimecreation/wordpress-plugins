// on verifie que la page est déjà chargée
window.addEventListener('DOMContentLoaded',function()
{
    // récupère le formulaire et l'input submit
    var myForm = document.querySelector('#my-form')
    var submitBtn = myForm.querySelector('#submitBtn')

    // ecoute d'un click sur l'input submit
    submitBtn.addEventListener('click',function(e)
    {
        // stop l'évément submit
        e.preventDefault()
        
        // get the inputs
        var nameInput = myForm.querySelector('#name')
        var emailInput = myForm.querySelector('#email')
        var urlInput = myForm.querySelector('#url')
        var actionInput = myForm.querySelector('#action')
        var nonceInput = myForm.querySelector('#my_submit_nonce')

        
        /**
         * envoie une requête ajax
         * documentation => https://developer.mozilla.org/fr/docs/Web/API/Fetch_API/Using_Fetch
         */
        fetch(urlInput.value,{
            method: "POST",
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `name=${nameInput.value}&email=${emailInput.value}&action=${actionInput.value}&my_submit_nonce=${nonceInput.value}`
        })
        .then(response => { return response.json() })
        .then( data => {
            // récupère le conteneur de feddback
            var feedbackContainer = document.querySelector('#my-form-feedback')
            if(data.success == true){
                // affiche le feesdback de succès
                feedbackContainer.innerHTML = data.message
            }
            if(data.success == false){
                // affiche le feedback d'erreur
                feedbackContainer.innerHTML = data.data.message
            }
            setTimeout(function(){ 
                // réintilise le formulaire et le conteneur de feedback
                    document.querySelector('#my-form').reset()
                    feedbackContainer.innerHTML = ''
                   
                },3000)
        })
        .catch(err => { console.log(err) })
    })
})