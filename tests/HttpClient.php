<?php
// Minimal browser for the tests: talks to the test server and keeps the session cookie.
// Redirects are not followed automatically, so tests can check where a page redirects.
class HttpClient
{
  public function __construct()
  {
    $this->cookieFile = tempnam(sys_get_temp_dir(), "inventory-test-cookies");
  }

  public function __destruct()
  {
    @unlink($this->cookieFile);
  }

  public function get(string $path, array $parameters = []): HttpResponse
  {
    if (!empty($parameters))
      $path .= (str_contains($path, "?") ? "&" : "?").http_build_query($parameters);
    return $this->request($path, []);
  }

  // $files maps the form field name to a local file path.
  public function post(string $path, array $fields = [], array $files = []): HttpResponse
  {
    foreach ($files as $name => $file)
      $fields[$name] = new CURLFile($file);
    return $this->request($path, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => empty($files) ? http_build_query($fields) : $fields]);
  }

  // Follows the redirect of the given response (if any) and returns the final page.
  public function follow(HttpResponse $response): HttpResponse
  {
    for ($i = 0; $i < 10 and $response->location() !== null; $i++)
      $response = $this->get($response->location());
    return $response;
  }

  public function login(string $username, string $password): void
  {
    $response = $this->post("/login_action", ["username" => $username, "password" => $password]);
    if ($response->location() !== "/")
      throw new Exception("Login of ".$username." failed: ".$response->location());
  }

  private function request(string $path, array $options): HttpResponse
  {
    if (!str_starts_with($path, "/"))
      $path = "/".$path;
    $headers = [];
    $curl = curl_init(TEST_APP_URL.$path);
    curl_setopt_array($curl, $options + [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => false,
      CURLOPT_COOKIEFILE => $this->cookieFile,
      CURLOPT_COOKIEJAR => $this->cookieFile,
      CURLOPT_HEADERFUNCTION => function ($curl, $line) use (&$headers)
      {
        $parts = explode(":", $line, 2);
        if (count($parts) == 2)
          $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
        return strlen($line);
      }]);
    $body = curl_exec($curl);
    if ($body === false)
      throw new Exception("Request to ".$path." failed: ".curl_error($curl));
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    unset($curl); // writes the cookie jar
    return new HttpResponse($status, $headers, $body);
  }

  private string $cookieFile;
}
