<?php
class HttpResponse
{
  public function __construct(public int $status, public array $headers, public string $body)
  {
  }

  // Target of a redirect, or null when the response isn't a redirect.
  public function location(): ?string
  {
    return $this->headers["location"] ?? null;
  }
}
