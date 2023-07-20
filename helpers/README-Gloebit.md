# Gloebit TrustFailure CERTIFICATE_VERIFY_FAILED

In some Linux distributions, mono doesn't handle LetsEncrypt certificates, which happen to be the ones used by Gloebit and many website.

If Gloebit doesn't work and you have messages like "TrustFailure (...) CERTIFICATE_VERIFY_FAILED" in your logs, don't worry, the fix is easy:

1. Make sure package ca-certificates-mono is installed, or install it with the command

  ```bash
  sudo apt install ca-certificates-mono
  ```

2. Download Letsencrypt root certificate from [their website](https://letsencrypt.org/certificates/). You can pick the first one, ISRG Root X1, in pem format

3. Make mono trust this root certificate

  ```bash
  sudo cert-sync isrgrootx1.pem
  # replace the name of the file if needed
  ```

It should work immediately, you shoudln't need to restart the sim.
