# Client journey

The cumulative client journey protects the smallest coherent server experience
from discovery through cleanup. Each implemented step has automated component
and integration evidence; wire-visible changes also require retail evidence.

1. Discover the exact bounded server advertisement.
2. Complete RakNet negotiation and connected transport setup.
3. Authenticate, negotiate encryption, and complete resource-pack setup.
4. Receive registries, player state, and the complete flat spawn view.
5. Observe correct grass, dirt, bedrock, air, biome, gravity, and breathing.
6. Move, rotate, jump, sprint, crouch, chat, and cross chunk boundaries.
7. Open and close the inventory repeatedly; move, split, select, and place from
   the server-owned starter grass stack without losing items.
8. Break and place grass with authoritative world mutation, inventory
   consumption, prediction correction, and visible progress.
9. Use ordinary controls without a valid packet being mistaken for corruption.
10. Disconnect, release all session and chunk ownership, and reconnect cleanly.
11. With two clients, verify peer visibility, movement and posture, attributed
    chat, synchronized interaction and held items, actor removal before list
    cleanup, and a ghost-free rejoin.
12. Consume and cancel an ordinary food, verify the authoritative stack and
    nutrition values, then reconnect and verify that nutrition persists.
13. Equip each armor slot and offhand, verify peer-visible equipment, take
    damage, and verify mitigation, durability, breakage, and reconnect state.
14. Craft through the personal grid and a reachable crafting table, exercise
    direct and recipe-book requests, close with inputs present, and verify that
    accepted, cancelled, stale, and repeated crafts neither lose nor duplicate
    items.
15. Open a chest, barrel, shulker box, and Ender Chest; transfer items in both
    directions; close and reopen each window; then restart and verify durable
    contents, private Ender Chest ownership, and immediate active-window updates.

Malformed, spoofed, phase-invalid, or oversized traffic remains fail-closed for
the affected session. Valid but unfinished gameplay receives an explicitly
documented no-op, rejection and resynchronization, or supported response; it is
not silently treated as malformed.

Passing only an encoder/decoder round trip is insufficient. Tests use literal
vectors or independent construction where practical, verify resource bounds,
and retain the regression that motivated each fix.

The multiplayer implementation has automated coverage, but step 11 and the new
item-use, equipment, crafting, and storage steps 12 through 15 remain open retail
qualification items.
Follow the repeatable checklist in
[player and multiplayer lifecycle](player-multiplayer.md) before advancing any
support claim.
