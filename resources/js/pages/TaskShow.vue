<template>
  <div>
    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-16">
      <span class="text-gray-400 text-sm">Loading task…</span>
    </div>

    <!-- Not found -->
    <div v-else-if="!task" class="text-center py-16">
      <p class="text-gray-500 font-medium">Task not found.</p>
      <router-link to="/" class="mt-4 inline-block text-primary-600 hover:text-primary-800 text-sm font-medium">
        Back to tasks
      </router-link>
    </div>

    <!-- Task detail -->
    <div v-else>
      <!-- Breadcrumb -->
      <div class="flex items-center space-x-2 text-sm text-gray-500 mb-4">
        <router-link to="/" class="hover:text-primary-600 transition-colors">Tasks</router-link>
        <span>/</span>
        <span class="text-gray-700 font-medium truncate max-w-xs">{{ task.name }}</span>
      </div>

      <!-- Header -->
      <div class="flex items-start justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ task.name }}</h1>
          <div class="flex flex-wrap items-center gap-2 mt-2">
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="statusClass(task.status)">
              {{ statusLabel(task.status) }}
            </span>
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="priorityClass(task.priority)">
              {{ task.priority }}
            </span>
            <span v-if="task.due_date" class="text-sm text-gray-500">
              Due {{ formatDate(task.due_date) }}
            </span>
          </div>
        </div>
        <div v-if="canManageTask" class="flex items-center space-x-3 shrink-0">
          <router-link
            :to="`/tasks/${task.id}/edit`"
            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
          >
            Edit
          </router-link>
          <button
            @click="deleteTask"
            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors"
          >
            Delete
          </button>
        </div>
      </div>

      <div
        v-if="liveNotification"
        class="mb-6 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800"
      >
        {{ liveNotification }}
      </div>

      <!-- Details card -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
        <div>
          <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Description</h2>
          <p v-if="task.description" class="text-gray-700 whitespace-pre-wrap text-sm leading-relaxed">
            {{ task.description }}
          </p>
          <p v-else class="text-gray-400 text-sm italic">No description provided.</p>
        </div>

        <div class="border-t border-gray-100 pt-4 grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
          <div>
            <span class="text-gray-500 font-medium">Created</span>
            <p class="text-gray-800 mt-1">{{ formatDate(task.created_at) }}</p>
          </div>
          <div>
            <span class="text-gray-500 font-medium">Last updated</span>
            <p class="text-gray-800 mt-1">{{ formatDate(task.updated_at) }}</p>
          </div>
          <div v-if="task.due_date">
            <span class="text-gray-500 font-medium">Due date</span>
            <p class="text-gray-800 mt-1" :class="isOverdue ? 'text-red-600 font-semibold' : ''">
              {{ formatDate(task.due_date) }}
            </p>
          </div>
        </div>
      </div>

      <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between gap-3 mb-4">
          <h2 class="text-lg font-semibold text-gray-800">Comments</h2>
          <span class="text-sm text-gray-400">{{ commentCountLabel }}</span>
        </div>

        <form class="space-y-3" @submit.prevent="submitComment">
          <div>
            <label for="comment-body" class="block text-sm font-medium text-gray-700 mb-1">Add a comment</label>
            <textarea
              id="comment-body"
              v-model="commentForm.body"
              rows="4"
              maxlength="1000"
              placeholder="Share an update or leave a note about this task"
              class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
            ></textarea>
            <div class="mt-1 flex items-center justify-between gap-3 text-xs">
              <p v-if="commentFieldError" class="text-red-600">{{ commentFieldError }}</p>
              <p v-else class="text-gray-400">Comments are visible on this task immediately after posting.</p>
              <span class="text-gray-400">{{ commentCharactersRemaining }} characters left</span>
            </div>
          </div>

          <div v-if="commentError" class="rounded-md bg-red-50 p-3">
            <p class="text-sm text-red-700">{{ commentError }}</p>
          </div>

          <div class="flex justify-end">
            <button
              type="submit"
              :disabled="submittingComment"
              class="inline-flex items-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              <span v-if="submittingComment">Posting…</span>
              <span v-else>Post comment</span>
            </button>
          </div>
        </form>

        <div class="mt-6 space-y-4">
          <div
            v-if="!task.comments || task.comments.length === 0"
            class="rounded-lg border border-dashed border-gray-200 px-4 py-6 text-center text-sm text-gray-400"
          >
            No comments yet.
          </div>

          <div
            v-for="comment in task.comments"
            :key="comment.id"
            class="rounded-lg border border-gray-200 px-4 py-4"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="font-medium text-sm text-gray-900">
                {{ comment.user?.name ?? 'Unknown user' }}
              </div>
              <div class="text-xs text-gray-400">
                {{ formatDateTime(comment.created_at) }}
              </div>
            </div>

            <p class="mt-3 whitespace-pre-wrap text-sm leading-relaxed text-gray-700">
              {{ comment.body }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuth } from '../composables/useAuth.js';
import api from '../composables/useApi.js';
import echo from '../echo.js';

const route = useRoute();
const router = useRouter();
const { user: currentUser } = useAuth();

const task = ref(null);
const loading = ref(true);
const liveNotification = ref('');
const commentForm = ref({
  body: '',
});
const commentError = ref('');
const commentFieldError = ref('');
const submittingComment = ref(false);
let notificationTimeout = null;

const canManageTask = computed(() => {
  return !!task.value && currentUser.value?.id === task.value.user_id;
});

const commentCountLabel = computed(() => {
  const count = task.value?.comments?.length ?? 0;

  return count === 1 ? '1 comment' : `${count} comments`;
});

const commentCharactersRemaining = computed(() => {
  return 1000 - commentForm.value.body.length;
});

const fetchTask = async () => {
  loading.value = true;
  try {
    const response = await api.get(`/tasks/${route.params.id}`);
    task.value = {
      ...response.data,
      comments: response.data.comments ?? [],
    };
  } catch (err) {
    console.error('Failed to fetch task:', err);
    task.value = null;
  } finally {
    loading.value = false;
  }
};

const deleteTask = async () => {
  if (!confirm('Delete this task? This cannot be undone.')) return;
  try {
    await api.delete(`/tasks/${route.params.id}`);
    router.push({ name: 'TaskList' });
  } catch (err) {
    console.error('Failed to delete task:', err);
  }
};

const showNotification = (message) => {
  liveNotification.value = message;

  clearTimeout(notificationTimeout);
  notificationTimeout = setTimeout(() => {
    liveNotification.value = '';
  }, 5000);
};

const upsertComment = (incomingComment) => {
  if (!task.value) {
    return;
  }

  const comments = task.value.comments ?? [];
  const existingIndex = comments.findIndex((comment) => comment.id === incomingComment.id);

  if (existingIndex >= 0) {
    comments.splice(existingIndex, 1, incomingComment);
    return;
  }

  task.value.comments = [...comments, incomingComment];
};

const submitComment = async () => {
  if (!task.value) {
    return;
  }

  commentError.value = '';
  commentFieldError.value = '';
  submittingComment.value = true;

  try {
    const response = await api.post(`/tasks/${task.value.id}/comments`, commentForm.value);
    upsertComment(response.data);
    commentForm.value.body = '';
  } catch (err) {
    commentFieldError.value = err.response?.data?.errors?.body?.[0] ?? '';
    commentError.value = err.response?.data?.message && !commentFieldError.value
      ? err.response.data.message
      : '';
  } finally {
    submittingComment.value = false;
  }
};

const subscribeToComments = () => {
  if (!task.value?.id || !echo) {
    return;
  }

  echo.private(`tasks.${task.value.id}`)
    .listen('.comment.created', (event) => {
      if (!event.comment) {
        return;
      }

      upsertComment(event.comment);

      if (event.comment.user_id !== currentUser.value?.id) {
        showNotification(event.message ?? `${event.comment.user?.name ?? 'Someone'} commented on ${task.value.name}.`);
      }
    });
};

const unsubscribeFromComments = () => {
  if (task.value?.id && echo) {
    echo.leave(`private-tasks.${task.value.id}`);
  }
};

const isOverdue = computed(() => {
  if (!task.value?.due_date || task.value.status === 'done') return false;
  return new Date(task.value.due_date) < new Date();
});

const statusClass = (status) => {
  const map = {
    todo: 'bg-gray-100 text-gray-700',
    in_progress: 'bg-yellow-100 text-yellow-800',
    done: 'bg-green-100 text-green-800',
  };
  return map[status] ?? 'bg-gray-100 text-gray-700';
};

const statusLabel = (status) => {
  const map = { todo: 'To Do', in_progress: 'In Progress', done: 'Done' };
  return map[status] ?? status;
};

const priorityClass = (priority) => {
  const map = {
    low: 'bg-blue-100 text-blue-800',
    medium: 'bg-orange-100 text-orange-800',
    high: 'bg-red-100 text-red-800',
  };
  return map[priority] ?? 'bg-gray-100 text-gray-700';
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return '';

  return new Date(dateStr).toLocaleString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
};

onMounted(async () => {
  await fetchTask();
  subscribeToComments();
});

onBeforeUnmount(() => {
  unsubscribeFromComments();
  clearTimeout(notificationTimeout);
});
</script>
