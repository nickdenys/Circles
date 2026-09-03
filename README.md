# Circles

Keep track of the albums you love, the ones you mean to get to, and what you thought of them.

Circles is a home for the way you actually listen. Not a feed, not an algorithm. Just your lists, your ratings, and your notes, connected to your Spotify account.

## What you can do

- **Build lists.** Group albums however you like. Start with the two that come ready to use, Listen Later for the on deck pile and Reviewed for everything you have sat with and scored.
- **Rate what you hear.** Score albums in half stars, leave a note on any record, and watch your taste take shape over time.
- **Move things around.** Drag albums into order, shift them between lists, and keep everything where you expect it.
- **Search all of Spotify.** Find any album in seconds and drop it straight into a list.
- **Turn a list into a playlist.** Spin any collection into a private Spotify playlist without leaving the page.

Circles works on your phone, and it looks good in the dark.

## Bring your own AI assistant

This is the part people tend to like. Circles speaks MCP, the protocol AI assistants use to work with real tools, so you can just ask.

> "Add the new Big Thief record to Listen Later."
>
> "What have I rated above four stars this year?"
>
> "Make me a playlist from my Reviewed list."

Generate a token in Settings, point Claude (or any MCP client) at it, and your library becomes something you can talk to.

## Getting started

You will need PHP, Node, and a Spotify developer app for the login keys.

```bash
composer run setup
composer run dev
```

That installs everything, prepares the database, and starts the app. Copy your Spotify client ID and secret into `.env`, then sign in with Spotify to get going. Access is limited to the accounts you list in `SIGNUP_ALLOWLIST`, so the app stays yours.

## Under the hood

Laravel 12 and React 19 (joined by Inertia), styled with Tailwind CSS, talking to the Spotify Web API.
