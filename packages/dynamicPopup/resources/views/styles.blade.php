<style>
  #newsletter-popup {
    display: flex;
    justify-content: center;
    align-content: center;
    background: transparent;
    font-family: Roboto;
    align-self: center;
    justify-self: center;
    max-height: 80vh;
  }

  #newsletter-popup-body {
    height: 100%;
    margin: 0px;
    padding: 0px;
  }

  #newsletter-popup-right-container {
    display: flex;
    flex-direction: column;
    height: 100%;
    margin: 0px;
    padding: 24px 24px 24px 12px;
  }

  #newsletter-popup-left-container {
    margin: 0;
    padding: 24px 12px 24px 24px;
    height: 100%;
  }

  #newsletter-popup-left-container img {
    border-radius: 16px;
  }

  #newsletter-popup h4 {
    font-weight: bold;
    font-family: Roboto;
    margin-bottom: 15px;
    color: #000;
    font-weight: 700;
    font-size: 32px;
    line-height: 38.4px;
    margin-bottom: 14px;
  }

  #newsletter-popup .subtext {
    font-family: Roboto;
    font-weight: 400;
    font-size: 15px;
    line-height: 28px;
    color: #6F6C90;
    margin-top: 10px;
    margin-bottom: 20px;
  }

  #newsletter-popup-content {
    width: 795px;
    height: 380px;
    border: none;
    border-radius: 24px;
    padding: 24px;
  }

  #subscribe_form {
    display: flex;
    justify-content: space-between;
    width: 100%;
    height: 56px;
    border: #c9c6dc 1px solid;
    height: fit-content;
    border-radius: 24px;
    margin-top: 16px;
  }

  #subscribe_btn {
    background-color: #FF6600;
    /* width: 20%; */
    color: white;
    border-radius: 24px;
    border: none;
    margin: 1px;
    padding: 0px 15px;
  }

  #email {
    border: none;
    width: 80%;
    margin-top: 10px;
    margin-bottom: 10px;
    margin-left: 15px;
    outline: none;
  }

  #newsletter-popup-hide {
    margin-top: auto;
    font-family: Roboto;
  }

  #newsletter-popup-hide .form-check-label {
    color: #6F6C90;
    font-family: Rubik;
    font-weight: 400;
    margin: 0px;
  }

  #newsletter-popup-hide .form-check-input {
    margin-bottom: 6px;
  }

  #newsletter-popup-banner>img {
    width: 100%;
    height: 100%;
    border-radius: 24px;
    object-fit: cover;
    max-width: 795px;
    max-height: 380px;
  }

  #newsletter-popup-banner-hide {
    position: absolute;
    bottom: 0px;
    right: 30px;
    font-size: 16px;
    line-height: 30px;
  }

  #newsletter-popup-banner-hide>label {
    margin: 0px;
  }

  #newsletter-popup .close-btn {
    position: absolute;
    width: 40px;
    height: 40px;
    top: 10px;
    right: 10px;
    opacity: .5;
    border-radius: 50%;
    background-color: #F4F0F0;
    border: none;
    z-index: 2;
    font-size: 1.5rem;
  }

  #newsletter-popup .close-btn:hover {
    opacity: 0.8;
  }

  #newsletter-popup .modal-header {
    border-bottom: none;
    position: relative;
    height: 0px;
    width: 0px;
  }

  @media (max-width: 768px) {
    #newsletter-popup-content {
      width: 100%;
      height: 100%;
      max-width: 795px;
    }

    #newsletter-popup-left-container {
      margin: 0;
      padding: 24px 14px 0px 14px;
      height: 100%;
    }

    #newsletter-popup h4 {
      margin-top: 10px;
    }

    #newsletter-popup .text-container {
      height: auto;
      width: 100%;
    }

    #subscribe_form {
      border: #c9c6dc 1px solid;
      border-radius: 24px;
      margin-bottom: 20px;
    }

    #subscribe_btn {
      min-width: 70px;
    }

    #newsletter-popup .form-check {
      margin-top: 0;
    }
  }

  /* Add custom css */
  {{ $popup['css'] ?? '' }}
</style>
