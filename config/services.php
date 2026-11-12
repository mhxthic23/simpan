<?php

return [

    'simpan_blockchain' => [
        'network' => env('BLOCKCHAIN_NETWORK', 'sepolia'),
        'chain_id' => (int) env('BLOCKCHAIN_CHAIN_ID', 11155111),
        'rpc_url' => env('BLOCKCHAIN_RPC_URL'),
        'contract_address' => env('BLOCKCHAIN_CONTRACT_ADDRESS'),
        'wallet_address' => env('BLOCKCHAIN_WALLET_ADDRESS'),
        // Reserved for an audited signer adapter; never expose this value to logs or web responses.
        'private_key' => env('BLOCKCHAIN_PRIVATE_KEY'),
        'explorer_url' => env('BLOCKCHAIN_EXPLORER_URL', 'https://sepolia.etherscan.io/tx/'),
    ],

    'pinata' => [
        'jwt' => env('PINATA_JWT'),
        'api_url' => env('PINATA_API_URL', 'https://api.pinata.cloud'),
        'gateway_url' => env('PINATA_GATEWAY_URL', 'https://gateway.pinata.cloud/ipfs'),
    ],

    'simpan_diagnostics' => [
        'ai_name' => env('SIMPAN_AI_PROVIDER_NAME', 'AI detector'),
        'ai_endpoint' => env('SIMPAN_AI_ENDPOINT'),
        'ai_key' => env('GPTZERO_API_KEY'),
        'plagiarism_name' => env('SIMPAN_PLAGIARISM_PROVIDER_NAME', 'Plagiarism checker'),
        'plagiarism_endpoint' => env('SIMPAN_PLAGIARISM_ENDPOINT'),
        'plagiarism_key' => env('COPYLEAKS_API_KEY'),
    ],

    // Reputation awards default to zero until the owner approves point policy.
    'simpan_reputation' => [
        'submission_token' => (int) env('SIMPAN_REPUTATION_SUBMISSION_TOKEN', 0),
        'grade_published_token' => (int) env('SIMPAN_REPUTATION_GRADE_PUBLISHED_TOKEN', 0),
        'voting_participation_token' => (int) env('SIMPAN_REPUTATION_VOTING_TOKEN', 0),
    ],

    'simpan_reminders' => [
        // Disabled by default until the project owner chooses a follow-up SLA.
        'followup_hours' => (int) env('SIMPAN_REMINDER_FOLLOWUP_HOURS', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
