<?php
/**
 * Vercel Serverless Function Entry Point for Rome & Ride
 */

// Error handling configuration
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Change working directory to project root
chdir(dirname(__DIR__));

// Forward to public front controller
require __DIR__ . '/../public/index.php';
