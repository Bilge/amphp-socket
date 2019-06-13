<?php

namespace Amp\Socket;

use Kelunik\Certificate\Certificate;

/**
 * Exposes a connection's negotiated TLS parameters.
 */
final class TlsInfo
{
    private $version;
    private $cipherName;
    private $cipherBits;
    private $cipherVersion;
    private $alpnProtocol;
    private $certificates;
    private $parsedCertificates;

    /**
     * Constructs a new instance from PHP's internal info.
     *
     * Always pass the info as obtained from PHP as this method might extract additional fields in the future.
     *
     * @param array $cryptoInfo Crypto info obtained via `stream_get_meta_data($socket->getResource())["crypto"]`.
     * @param array $tlsContext Context obtained via `stream_context_get_options($socket->getResource())["ssl"])`.
     *
     * @return self
     */
    public static function fromMetaData(array $cryptoInfo, array $tlsContext): self
    {
        return new self(
            $cryptoInfo["protocol"],
            $cryptoInfo["cipher_name"],
            $cryptoInfo["cipher_bits"],
            $cryptoInfo["cipher_version"],
            $cryptoInfo["alpn_protocol"] ?? null,
            \array_merge([$tlsContext["peer_certificate"]] ?: [], $tlsContext["peer_certificate_chain"] ?? [])
        );
    }

    private function __construct(string $version, string $cipherName, int $cipherBits, string $cipherVersion, ?string $alpnProtocol, array $certificates)
    {
        $this->version = $version;
        $this->cipherName = $cipherName;
        $this->cipherBits = $cipherBits;
        $this->cipherVersion = $cipherVersion;
        $this->alpnProtocol = $alpnProtocol;
        $this->certificates = $certificates;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getCipherName(): string
    {
        return $this->cipherName;
    }

    public function getCipherBits(): int
    {
        return $this->cipherBits;
    }

    public function getCipherVersion(): string
    {
        return $this->cipherVersion;
    }

    public function getApplicationLayerProtocol(): ?string
    {
        return $this->alpnProtocol;
    }

    /** @return Certificate[] */
    public function getPeerCertificates(): array
    {
        if ($this->parsedCertificates === null) {
            $this->parsedCertificates = \array_map(static function ($resource) {
                return new Certificate($resource);
            }, $this->certificates);
        }

        return $this->parsedCertificates;
    }
}