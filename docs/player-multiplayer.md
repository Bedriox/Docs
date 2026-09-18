# Player and multiplayer lifecycle

Bedriox implements a narrow authoritative player and peer-visibility slice for
its fixed-flat world. The implementation has automated coverage, but the
two-client retail qualification described below is still required before this
behavior is presented as supported.

## Authoritative ownership

The world simulation exclusively owns each mutable `Player` aggregate. A
player combines:

- an authenticated identity and display name;
- a server-assigned runtime actor ID;
- validated feet position, body yaw, head yaw, pitch, grounded state, predicted
  vertical velocity, sneaking, and sprinting state; and
- a server-owned fixed-size survival inventory, selected hotbar slot, cursor
  stack, and stack-request lineage; and
- bounded chat and emote rate-limit state.

`PlayerRegistry` indexes the same aggregate by session ID, authenticated UUID,
and runtime actor ID. It rejects duplicate live values and capacity overflow,
returns deterministic immutable snapshots for event projection, and removes
all indexes together. Packets, sockets, ciphers, and chunk views remain owned
by runtime/session code and are never exposed through `Player`.

Decoded network input cannot mutate a player. The play channel validates the
session phase, actor, fields, and limits, then creates an immutable command.
The 20 TPS simulation consumes that command and publishes immutable events.

## Joining and peer visibility

A player becomes authoritative only after spawn terrain has been released,
the client acknowledges its assigned local actor, and the simulation accepts
the join. The join event contains the new snapshot, a deterministic snapshot
of existing peers, and recipient session IDs.

Authoritative join first publishes the authenticated player-list entry. Actor
visibility is then gated by the recipient's sent chunk view: Bedriox does not
spawn an actor in a chunk that recipient has not received. For each visible
peer relationship, the protocol projection preserves this order:

1. ensure the authenticated player-list entry was added;
2. add the player actor with its server-assigned runtime ID and current pose;
3. publish baseline actor metadata, including the display name and posture.

The same sequence introduces the joining actor to existing clients and each
existing actor to the joining client once the required chunks are available.
Leaving a recipient's sent view removes only the actor; returning to the view
adds the actor and baseline metadata again without duplicating player-list
membership. Client-supplied actor identity and chat attribution are not trusted.

## Movement, posture, and corrections

Accepted movement is projected to peers with `MoveActorAbsolute`. Feet
coordinates remain authoritative inside the simulation; the packet adapter
alone applies Bedrock's player eye-height projection. Body yaw, head yaw,
pitch, and the authoritative grounded flag are included.

Sneaking and sprinting are durable player state. A posture transition follows
that movement with `SetActorData`; unchanged posture does not create redundant
metadata traffic. An invalid or over-budget prediction does not reach peers.
Instead, only its owner receives an authoritative `MovePlayer` reset.
Movement and emotes are delivered only to peers for which the actor is visible.

Chat uses the authenticated display name and deterministic simulation order.
The bounded emote path relays a validated UUID intent to peers without trusting
client-supplied duration, account identity, platform identity, or flags.

## Inventory and block interaction

The implemented inventory slice owns 36 main-inventory slots, the selected
hotbar slot, and one cursor stack. A joining player receives one stack of 64
grass blocks. The main inventory can be opened and closed repeatedly, and
bounded Take, Place, and Swap requests can move or split stacks between the
main inventory, hotbar, and cursor. Successful requests receive authoritative
slot results; stale stack IDs, invalid counts, unsupported containers, and
failed merges receive a bounded correction without disconnecting the player.

Grass breaking and placement mutate the in-memory fixed-flat world. The server
revalidates reach, the current block, player collision, the selected slot, and
the server-owned held stack before committing a change. Placement consumes one
item. Rejected client predictions are repaired from authoritative world and
inventory state. Accepted block changes and held-item changes are synchronized
only to peers that can currently see the affected chunk or actor.

This is not a general inventory or item system. Crafting, armor manipulation,
containers, item drops, tools, durability, block drops, and items or placeable
blocks other than the starter grass stack remain unavailable.

## Disconnect, reconnect, and failure containment

Disconnect processing removes the player from every authoritative registry
index before publishing departure. Each remaining peer receives `RemoveActor`
before `PlayerListRemove`, preventing a departed player from remaining as a
visible ghost actor. Repeated cleanup is idempotent. A reconnect is a new
runtime session and actor admission; it cannot reuse stale live registry state.

Malformed, wrong-actor, out-of-phase, oversized, or queue-exhausting input
closes only the affected session. If encoding or the bounded fan-out of a world
event fails, the runtime identifies the session whose command caused that event
and disconnects that causal session without disconnecting healthy recipients.
An ownerless event failure is treated as a runtime invariant failure because no
client can be isolated safely.

## Automated evidence

The Bedriox gate covers registry capacity and cleanup, duplicate rejection,
deterministic peer snapshots, posture transitions, join packet ordering,
chunk-gated actor visibility, absolute peer movement, owner-only correction,
actor-before-list departure, reconnect cleanup, and causal-session isolation
for encoder and fan-out budget failures. Workspace verification also exercises
the pinned packet codecs in Protocol.

These tests establish implementation contracts, not retail compatibility.

## Retail qualification checklist

Run this only after all repository gates and pushed CI are green. Record the
exact Bedriox/component commits, Minecraft build and platform, sanitized
settings, date, and result without retaining account identifiers, JWTs, keys,
or raw personal packet captures.

1. Start from clean server state and join with client A.
2. Join with client B and verify that both players appear once with the correct
   name and skin.
3. From each client, walk, look, jump, sprint, and crouch while the other client
   observes position, head rotation, grounded motion, and posture.
4. Exchange attributed chat in both directions and verify order.
5. Cross positive and negative chunk boundaries while both sessions remain
   responsive and terrain continues loading.
6. Open and close the main inventory repeatedly. Split the grass stack between
   hotbar slots, move it through the cursor, select the resulting stack, and
   place from it without losing the remaining items.
7. Break and place grass from each client. Verify both clients observe the same
   world mutation, held stack, and block-break progress, and that placement
   consumes exactly one server-owned item.
8. Disconnect client A and verify that client B loses both the actor and
   player-list entry immediately.
9. Rejoin client A and verify one fresh actor, one list entry, current movement,
   and no ghost from the earlier session.
10. Repeat with client B as the departing player and inspect sanitized server
   diagnostics for unexpected disconnects or runtime failures.

This manual pass does not by itself complete the broader soak, packet-loss,
cross-platform, memory-recovery, or performance gates in the
[testing guide](testing.md).

## Current boundary

The lifecycle slice provides only the narrow grass inventory and interaction
behavior described above. It does not provide crafting, general item use,
containers, combat, mobs, commands, permissions, plugins, persistence, or
general block behavior. The world remains an in-memory fixed-flat generator. See
[known limitations](known-limitations.md) and [compatibility](compatibility.md)
for the public support boundary.
