# Processing stations and experience

Bedriox owns processing results, station state, and player experience. Client
recipe selections, displayed outputs, costs, progress, and stack identifiers
are intent or presentation data; they never authorize an inventory or world
mutation.

## Furnace family and campfires

Furnaces, blast furnaces, and smokers use the same authoritative lifecycle with
a typed station kind. The server selects an admitted recipe and fuel from the
current inputs, advances bounded burn and cook time, derives the result, stores
the recipe experience, and commits changed inventory and block-entity state
together. The client cannot write the result slot or claim progress.

Campfires and soul campfires persist four independent cooking positions. Each
position owns its input, remaining duration, and result. Breaking, replacing,
or unloading a station follows the same chunk-owned persistence and exactly-once
item-return rules as other persistent block entities.

Only active stations enter the bounded due-time scheduler. Idle stations are
not scanned every tick, and unloaded stations are removed from the scheduler
until their persisted chunk is loaded again. Reload catch-up is bounded rather
than replaying an unlimited number of missed ticks.

## Transient stations

Stonecutters, smithing tables, anvils, grindstones, enchanting tables, looms,
and cartography tables keep inputs in the player's active window session. Those
inputs are not world storage. Closing, disconnecting, teleporting, replacing
the backing block, or invalidating the window returns them exactly once through
the authoritative inventory and bounded item-overflow path.

Bedriox derives each preview and result from the current server-owned inputs.
Stonecutting and smithing use admitted indexed recipes. Anvils own repair,
combination, rename, prior-work, damage, and level costs. Grindstones preserve
curses while deriving repaired or disenchanted results and experience.
Enchanting options are deterministic for the player seed and current
bookshelves, item, and lapis state. Loom and cartography operations preserve
their bounded item data and map identities.

Composters and cauldrons also use typed authoritative transitions. Brewing
stands retain the lifecycle documented in
[effects, particles, potions, and brewing](effects-and-particles.md).

## Experience and orbs

A player record stores one bounded total-experience value. Level and progress
are derived views, persist with the player, and are synchronized as typed player
attributes. Furnace extraction, grindstones, enchanting, commands, plugins,
death, and collected orbs all use the same change path.

Experience orbs are server-owned entities with bounded value, motion,
attraction, merging, pickup delay, lifetime, visibility, and capacity. A client
cannot create an orb, choose its value, or award itself experience. Collection
removes or reduces the orb and changes player experience as one authoritative
transition.

API 0.4 exposes `Player::getExperience()`. Its generation-bound manager returns
an immutable snapshot with total points, derived level, and progress, and
accepts bounded `setTotalPoints()`, `addPoints()`, and `removePoints()` requests.
Retaining a player snapshot does not grant authority over a later connection.

## Atomicity and duplication safety

Every accepted request validates the active window generation, station and
world position, reach, typed slots, stack lineage, inventory revisions, recipe,
output capacity, and experience cost. Bedriox stages the complete change,
dispatches plugin pre-events, revalidates affected revisions, and commits once.
A rejection or listener failure commits nothing and sends authoritative
correction state where needed.

Replay, stale stack identities, repeated result collection, a full destination,
close, disconnect, death, teleport, block replacement, chunk unload, shutdown,
plugin cancellation, and plugin failure must neither duplicate nor lose items
or experience. Result slots are derived views, not writable client storage.

## Plugin events

Processing and experience use paired event names. Present-tense pre-events such
as `PlayerExperienceChangeEvent`, `ExperienceOrbSpawnEvent`,
`FurnaceFuelConsumeEvent`, `FurnaceStartSmeltEvent`, `FurnaceSmeltEvent`, and
`CampfireCookEvent` are cancellable after core validation and before commit.
Their documented replacement values remain bounded and are revalidated.
Past-tense events such as `PlayerExperienceChangedEvent`,
`ExperienceOrbSpawnedEvent`, `FurnaceFuelConsumedEvent`,
`FurnaceStartedSmeltingEvent`, `FurnaceSmeltedEvent`, and
`CampfireCookedEvent` report committed outcomes and are immutable.

Transient stations, enchanting, extraction, composters, and cauldrons follow
the same pre-event/committed-event rule. Existing inventory open, close,
transaction, and committed events still apply to station windows. Events fire
at semantic transitions, never once per processing tick. Cancellation and
replacement cannot bypass ownership, reach, slot types, stack limits,
experience bounds, revision checks, or the all-or-nothing commit.

The public values use canonical item identifiers, typed station kinds, block
positions, immutable item stacks, and typed causes. They do not expose numeric
wire IDs, mutable block entities, window IDs, stack-network IDs, scheduler
entries, or recipe storage internals. See [plugins and API 0.4](plugins.md) and
the tested [ExamplePlugin](https://github.com/Bedriox/ExamplePlugin).

## Data boundary

Data supplies approved, immutable, source-neutral records and indexed views for
recipes, fuels, enchantments, patterns, trims, composting, and cauldron
transformations. Catalog presence alone never executes a recipe or authorizes a
result. Bedriox owns matching, scheduling, validation, experience, station
state, and commits; Protocol owns only bounded wire representation.

## Qualification checklist

Automated coverage includes recipe and fuel selection, duration and property
updates, persistence, unload and restart, transient-window cleanup, experience
math and persistence, orb movement and pickup, plugin cancellation and failure,
stale and replayed requests, repeated extraction, full inventories, and two
viewers. Retail qualification must additionally exercise furnace, blast
furnace, smoker, campfire, each transient station, experience costs and gains,
orb collection, restart mid-process, and multiplayer synchronization without
item or experience duplication.
