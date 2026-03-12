<?php

// Taken from http://net.tutsplus.com/tutorials/php/secure-your-forms-with-form-keys/

class formKey
{
    // Here we store the generated form key
    private $formKey;

    // Here we store the old form key (more info at step 4)
    private $old_formKey;

    // The constructor stores the form key (if one excists) in our class variable
    function __construct()
    {
        // We need the previous key so we store it
        if (isset($_SESSION["form_key"])) {
            $this->old_formKey = $_SESSION["form_key"];
        }
    }

    //Function to generate the form key
    private function generateKey()
    {
        return bin2hex(random_bytes(32));
    }

    //Function to output the form key
    public function outputKey()
    {
        // Generate the key and store it inside the class
        $this->formKey = $this->generateKey();

        // Store the form key in the session
        $_SESSION["form_key"] = $this->formKey;

        //Output the form key
        return $this->formKey;
    }

    // Function that validated the form key POST data
    public function validate()
    {
        //We use the old formKey and not the new generated version
        if (
            isset($_POST["form_key"]) &&
            is_string($_POST["form_key"]) &&
            is_string($this->old_formKey) &&
            hash_equals($this->old_formKey, $_POST["form_key"])
        ) {
            // The key is valid, return true.
            return true;
        } else {
            // The key is invalid, return false.
            return false;
        }
    }
}
?>
