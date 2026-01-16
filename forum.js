// forum.js - isolated forum frontend logic (namespace: forum)
(function () {
  const apiBase = "php/forum";

  // Helper: fetch JSON with error handling
  async function jfetch(url, opts = {}) {
    const res = await fetch(
      url,
      Object.assign({ credentials: "include" }, opts)
    );
    return res.json();
  }

  // ---------- Forum Listing Page ----------
  async function loadCategories() {
    const el = document.getElementById("categories");
    const sel = document.getElementById("postCategory");
    if (!el || !sel) return;
    el.innerHTML = "Loading...";
    try {
      const data = await jfetch(`${apiBase}/get_categories.php`);
      if (!data.success) {
        el.innerHTML = "Unable to load.";
        return;
      }
      el.innerHTML = "";
      sel.innerHTML = "";
      data.categories.forEach((c) => {
        const a = document.createElement("a");
        a.href = "#";
        a.className = "list-group-item list-group-item-action";
        a.textContent = c.name + (c.description ? " — " + c.description : "");
        a.dataset.cid = c.category_id;
        a.addEventListener("click", (e) => {
          e.preventDefault();
          loadPosts(c.category_id, c.name);
        });
        el.appendChild(a);

        const opt = document.createElement("option");
        opt.value = c.category_id;
        opt.textContent = c.name;
        sel.appendChild(opt);
      });
    } catch (e) {
      el.innerHTML = "Error";
    }
  }

  async function loadPosts(categoryId = 0, title = "Recent Posts") {
    const postsEl = document.getElementById("posts");
    const titleEl = document.getElementById("postsTitle");
    if (!postsEl || !titleEl) return;
    titleEl.textContent = title;
    postsEl.innerHTML = "Loading...";

    const url = categoryId
      ? `${apiBase}/get_posts.php?category_id=${encodeURIComponent(categoryId)}`
      : `${apiBase}/get_posts.php`;
    try {
      const data = await jfetch(url);
      if (!data.success) {
        postsEl.innerHTML = "Unable to load posts.";
        return;
      }
      postsEl.innerHTML = "";
      data.posts.forEach((p) => {
        const card = document.createElement("div");
        card.className = "card p-3 mb-2 post-card";
        const h = document.createElement("h6");
        h.textContent = p.title;
        const meta = document.createElement("div");
        meta.className = "text-muted small";
        meta.textContent = `By user ${p.user_id} • ${p.reply_count} replies • ${p.created_at}`;
        const body = document.createElement("p");
        body.textContent = p.body;
        card.appendChild(h);
        card.appendChild(meta);
        card.appendChild(body);
        card.addEventListener("click", () => {
          window.location.href = `forum_post.html?post_id=${p.post_id}`;
        });
        postsEl.appendChild(card);
      });
      if (data.posts.length === 0)
        postsEl.innerHTML = '<div class="text-muted">No posts yet.</div>';
    } catch (e) {
      postsEl.innerHTML = "Error loading posts.";
    }
  }

  async function createPostHandler(e) {
    e.preventDefault();
    const category = document.getElementById("postCategory").value;
    const title = document.getElementById("postTitle").value.trim();
    const body = document.getElementById("postBody").value.trim();
    if (!title || !body) {
      alert("Please enter title and body");
      return;
    }
    try {
      const res = await jfetch(`${apiBase}/create_post.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ category_id: category, title, body }),
      });
      if (res.success) {
        document.getElementById("postTitle").value = "";
        document.getElementById("postBody").value = "";
        loadPosts(category);
      } else alert(res.message || "Failed to create post");
    } catch (err) {
      alert("Network error");
    }
  }

  // ---------- Post Detail Page ----------
  async function loadPostDetail() {
    const params = new URLSearchParams(location.search);
    const postId = params.get("post_id");
    if (!postId) return;

    // Fetch posts endpoint and filter for single post (simple approach)
    try {
      const res = await jfetch(
        `${apiBase}/get_posts.php?category_id=0&page=1&page_size=200`
      );
      if (!res.success) return;
      const post = res.posts.find((p) => String(p.post_id) === String(postId));
      if (!post) return;
      document.getElementById("postTitle").textContent = post.title;
      document.getElementById("postBody").textContent = post.body;
      document.getElementById(
        "postMeta"
      ).textContent = `By user ${post.user_id} • ${post.created_at}`;
      await loadReplies(postId);
    } catch (e) {
      console.error(e);
    }
  }

  async function loadReplies(postId) {
    const el = document.getElementById("replies");
    if (!el) return;
    el.innerHTML = "Loading...";
    try {
      const res = await jfetch(
        `${apiBase}/get_replies.php?post_id=${encodeURIComponent(postId)}`
      );
      if (!res.success) {
        el.innerHTML = "Unable to load replies.";
        return;
      }
      el.innerHTML = "";
      res.replies.forEach((r) => {
        const d = document.createElement("div");
        d.className = "mb-2";
        const meta = document.createElement("div");
        meta.className = "text-muted small";
        meta.textContent = `User ${r.user_id} • ${r.created_at}`;
        const body = document.createElement("div");
        body.className = "reply";
        body.textContent = r.body;
        d.appendChild(meta);
        d.appendChild(body);
        el.appendChild(d);
      });
      if (res.replies.length === 0)
        el.innerHTML = '<div class="text-muted">No replies yet.</div>';
    } catch (e) {
      el.innerHTML = "Error loading replies.";
    }
  }

  async function replyHandler(e) {
    e.preventDefault();
    const params = new URLSearchParams(location.search);
    const postId = params.get("post_id");
    const body = document.getElementById("replyBody").value.trim();
    if (!body) {
      alert("Please enter a reply");
      return;
    }
    try {
      const res = await jfetch(`${apiBase}/create_reply.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ post_id: postId, body }),
      });
      if (res.success) {
        document.getElementById("replyBody").value = "";
        loadReplies(postId);
      } else alert(res.message || "Failed to post reply");
    } catch (e) {
      alert("Network error");
    }
  }

  // ---------- Init ----------
  document.addEventListener("DOMContentLoaded", () => {
    // If on forum.html
    if (document.getElementById("categories")) {
      loadCategories();
      loadPosts();
      const form = document.getElementById("createPostForm");
      form && form.addEventListener("submit", createPostHandler);
      const refresh = document.getElementById("refreshBtn");
      refresh && refresh.addEventListener("click", () => loadPosts());
    }

    // If on forum_post.html
    if (
      document.getElementById("postTitle") &&
      new URLSearchParams(location.search).get("post_id")
    ) {
      loadPostDetail();
      const rform = document.getElementById("replyForm");
      rform && rform.addEventListener("submit", replyHandler);
    }
  });
})();
