// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

/// @notice Stores only opaque hashes. Never pass names, user IDs, grades, or document contents.
contract SimpanRegistry {
    address public owner;
    mapping(address => bool) public publishers;
    mapping(bytes32 => bool) public recorded;

    event EvidenceRecorded(bytes32 indexed eventTypeHash, bytes32 indexed referenceHash, bytes32 payloadSha256, bytes32 idempotencyHash);
    event PublisherChanged(address indexed publisher, bool enabled);

    error NotOwner();
    error NotPublisher();
    error DuplicateEvent();

    constructor() {
        owner = msg.sender;
        publishers[msg.sender] = true;
    }

    function setPublisher(address publisher, bool enabled) external {
        if (msg.sender != owner) revert NotOwner();
        publishers[publisher] = enabled;
        emit PublisherChanged(publisher, enabled);
    }

    function recordEvidence(bytes32 eventTypeHash, bytes32 referenceHash, bytes32 payloadSha256, bytes32 idempotencyHash) external {
        if (!publishers[msg.sender]) revert NotPublisher();
        if (recorded[idempotencyHash]) revert DuplicateEvent();
        recorded[idempotencyHash] = true;
        emit EvidenceRecorded(eventTypeHash, referenceHash, payloadSha256, idempotencyHash);
    }
}
