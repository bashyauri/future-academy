import axios from "axios";
import api from "@/lib/api";
import {
  clearSubjectQuestionBank,
  getDownloadedQuestionCount,
  initOfflineDatabase,
  saveSubjectQuestionPage,
  updateSubjectDownloadRecord,
} from "@/lib/offlineDb";

type DownloadedSubject = {
  id: number;
  name: string;
};

type QuestionOption = {
  id: number;
  label: string | null;
  option_text: string | null;
  is_correct: boolean;
};

type QuestionPayload = {
  id: number;
  question_text: string | null;
  question_text_html: string | null;
  question_image: string | null;
  explanation: string | null;
  explanation_html: string | null;
  explanation_image: string | null;
  subject_id: number;
  topic_id: number | null;
  exam_type_id: number | null;
  exam_year: number | null;
  year: number | null;
  difficulty: string | null;
  is_mock: boolean;
  mock_group_id: number | null;
  options: QuestionOption[];
};

function extractQuestions(payload: unknown): QuestionPayload[] {
  if (Array.isArray(payload)) {
    return payload as QuestionPayload[];
  }

  if (payload && typeof payload === "object") {
    const maybe = payload as { data?: unknown };

    if (Array.isArray(maybe.data)) {
      return maybe.data as QuestionPayload[];
    }
  }

  return [];
}

export async function isSubjectDownloaded(subjectId: number): Promise<boolean> {
  await initOfflineDatabase();

  const count = await getDownloadedQuestionCount(subjectId);

  return count > 0;
}

export async function downloadSubjectQuestionBank(
  subjectId: number,
): Promise<number> {
  await initOfflineDatabase();

  await updateSubjectDownloadRecord(subjectId, "downloading", 0, null);
  await clearSubjectQuestionBank(subjectId);

  try {
    const firstPageResponse = await api.get(`/subjects/${subjectId}/download`, {
      params: { per_page: 100, page: 1 },
    });

    const pagination = firstPageResponse.data?.pagination ?? {};
    const lastPage = pagination.last_page ?? 1;

    await saveSubjectQuestionPage(
      extractQuestions(firstPageResponse.data?.questions),
    );

    for (let page = 2; page <= lastPage; page += 1) {
      const pageResponse = await api.get(`/subjects/${subjectId}/download`, {
        params: { per_page: 100, page },
      });

      await saveSubjectQuestionPage(
        extractQuestions(pageResponse.data?.questions),
      );
    }

    const finalCount = await getDownloadedQuestionCount(subjectId);
    await updateSubjectDownloadRecord(
      subjectId,
      "downloaded",
      finalCount,
      null,
    );

    return finalCount;
  } catch (error: unknown) {
    const downloadedCount = await getDownloadedQuestionCount(subjectId);
    const message = axios.isAxiosError(error)
      ? (error.response?.data?.message ??
        `Download failed with status ${error.response?.status ?? "unknown"}.`)
      : error instanceof Error
        ? error.message
        : "Download failed.";

    await updateSubjectDownloadRecord(
      subjectId,
      "failed",
      downloadedCount,
      message,
    );

    throw new Error(message);
  }
}

export async function downloadMissingSubjects(
  subjects: DownloadedSubject[],
): Promise<{
  downloadedNow: DownloadedSubject[];
  alreadyAvailable: DownloadedSubject[];
}> {
  const downloadedNow: DownloadedSubject[] = [];
  const alreadyAvailable: DownloadedSubject[] = [];

  for (const subject of subjects) {
    const availableOffline = await isSubjectDownloaded(subject.id);

    if (availableOffline) {
      alreadyAvailable.push(subject);
      continue;
    }

    await downloadSubjectQuestionBank(subject.id);
    downloadedNow.push(subject);
  }

  return { downloadedNow, alreadyAvailable };
}
