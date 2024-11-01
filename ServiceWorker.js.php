<?php
/**
 * Public file for Service Worker
 */
header("Service-Worker-Allowed: /");
header("Content-Type: application/javascript");
header("X-Robots-Tag: none");
if (VILFIO_DEV) {
    echo "importScripts('https://vilfiodev.eu/vilf-io-service-worker.js');";
} else {
    echo "importScripts('https://vilf.io/vilf-io-service-worker.js');";
}